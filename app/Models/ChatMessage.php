<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Encryptable;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes, Encryptable;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'image_url',
        'voice_url',
        'voice_effect',
        'video_url',
        'gift_transaction_id',
        'anonymous_message_id',
        'story_reply_id',
        'reply_to_message_id',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'media_url',
        'media_full_url',
    ];

    /**
     * Champs à chiffrer
     */
    protected $encryptable = ['content'];

    /**
     * Types de messages
     */
    const TYPE_TEXT = 'text';
    const TYPE_GIFT = 'gift';
    const TYPE_SYSTEM = 'system';
    const TYPE_IMAGE = 'image';
    const TYPE_VOICE = 'voice';
    const TYPE_VIDEO = 'video';

    // ==================== RELATIONS ====================

    /**
     * Conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Expéditeur
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withoutTrashed();
    }

    /**
     * Transaction de cadeau (si type = gift)
     */
    public function giftTransaction(): BelongsTo
    {
        return $this->belongsTo(GiftTransaction::class);
    }

    /**
     * Message anonyme auquel ce message répond (si applicable)
     */
    public function anonymousMessage(): BelongsTo
    {
        return $this->belongsTo(AnonymousMessage::class);
    }

    /**
     * Message auquel ce message répond (chat)
     */
    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_message_id');
    }

    /**
     * Réponse à une story (si ce message provient d'un story reply)
     */
    public function storyReply(): BelongsTo
    {
        return $this->belongsTo(StoryReply::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Accessor pour le contenu (déchiffré automatiquement)
     */
    public function getContentAttribute($value): ?string
    {
        // Si pas de valeur, retourner null
        if (empty($value)) {
            return null;
        }

        // Si c'est un message système ou cadeau, pas de chiffrement
        if ($this->type === self::TYPE_SYSTEM || $this->type === self::TYPE_GIFT) {
            return $value;
        }

        // Forcer le déchiffrement pour les messages texte
        return $this->getDecryptedAttribute('content');
    }

    /**
     * Aperçu du contenu du message (déchiffré et tronqué)
     */
    public function getContentPreviewAttribute(): string
    {
        if ($this->type === self::TYPE_GIFT) {
            return '🎁 Cadeau envoyé';
        }

        if ($this->type === self::TYPE_IMAGE) {
            return '📷 Image';
        }

        if ($this->type === self::TYPE_VOICE) {
            return '🎤 Message vocal';
        }

        if ($this->type === self::TYPE_VIDEO) {
            return '🎬 Vidéo';
        }

        if ($this->type === self::TYPE_SYSTEM) {
            // Le contenu système n'est pas chiffré
            return $this->attributes['content'] ?? '';
        }

        // Pour les messages texte, forcer le déchiffrement directement
        $content = $this->getDecryptedAttribute('content') ?? '';

        if (strlen($content) > 50) {
            return substr($content, 0, 47) . '...';
        }

        return $content;
    }

    /**
     * Le message est-il un cadeau ?
     */
    public function getIsGiftAttribute(): bool
    {
        return $this->type === self::TYPE_GIFT;
    }

    /**
     * Le message est-il un message système ?
     */
    public function getIsSystemAttribute(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    /**
     * URL du média selon le type
     */
    public function getMediaUrlAttribute(): ?string
    {
        return match ($this->type) {
            self::TYPE_IMAGE => $this->image_url,
            self::TYPE_VOICE => $this->voice_url,
            self::TYPE_VIDEO => $this->video_url,
            default => null,
        };
    }

    /**
     * URL complète du média selon le type
     */
    public function getMediaFullUrlAttribute(): ?string
    {
        $mediaUrl = $this->media_url;
        if (!$mediaUrl) {
            return null;
        }

        if (str_starts_with($mediaUrl, 'http')) {
            return $mediaUrl;
        }

        $baseUrl = config('app.url');
        if (request()) {
            $baseUrl = request()->getSchemeAndHttpHost();
        }

        return rtrim($baseUrl, '/') . '/storage/' . ltrim($mediaUrl, '/');
    }

    // ==================== SCOPES ====================

    /**
     * Messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Messages d'un type spécifique
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== METHODS ====================

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Créer un message système
     */
    public static function createSystemMessage(Conversation $conversation, string $content): self
    {
        return self::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $conversation->participant_one_id, // Arbitraire pour système
            'content' => $content,
            'type' => self::TYPE_SYSTEM,
            'is_read' => true,
        ]);
    }

    /**
     * Créer un message de cadeau
     */
    public static function createGiftMessage(
        Conversation $conversation,
        User $sender,
        GiftTransaction $transaction,
        string $message = null
    ): self {
        return self::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'content' => $message ?? "🎁 A envoyé un cadeau : {$transaction->gift->name}",
            'type' => self::TYPE_GIFT,
            'gift_transaction_id' => $transaction->id,
        ]);
    }
}
