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
        'gift_transaction_id',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Transaction de cadeau (si type = gift)
     */
    public function giftTransaction(): BelongsTo
    {
        return $this->belongsTo(GiftTransaction::class);
    }

    // ==================== ACCESSORS ====================

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
