<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_id',
        'target_user_id',
        'type',
        'conversation_id',
        'message_id',
        'amount',
        'status',
        'payment_reference',
        'starts_at',
        'expires_at',
        'auto_renew',
    ];

    protected $casts = [
        'amount' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Prix de l'abonnement (450 FCFA/mois selon cahier des charges)
     */
    const MONTHLY_PRICE = 450;

    /**
     * Types d'abonnement
     */
    const TYPE_CONVERSATION = 'conversation';
    const TYPE_MESSAGE = 'message';

    /**
     * Statuts
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // ==================== RELATIONS ====================

    /**
     * Abonné (celui qui paie)
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    /**
     * Utilisateur cible (dont l'identité est révélée)
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Conversation associée
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Message associé (si type = message)
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(AnonymousMessage::class, 'message_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * L'abonnement est-il actif ?
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at
            && $this->expires_at->isFuture();
    }

    /**
     * L'abonnement est-il expiré ?
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Jours restants
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->expires_at);
    }

    /**
     * Montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    // ==================== SCOPES ====================

    /**
     * Abonnements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Abonnements expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Abonnements expirant bientôt (dans les 3 prochains jours)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDays(3)]);
    }

    /**
     * Par abonné
     */
    public function scopeBySubscriber($query, int $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }

    /**
     * Pour une conversation
     */
    public function scopeForConversation($query, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    // ==================== METHODS ====================

    /**
     * Activer l'abonnement
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        // Révéler l'identité si c'est pour un message spécifique
        if ($this->type === self::TYPE_MESSAGE && $this->message) {
            $this->message->revealIdentity($this);
        }
    }

    /**
     * Renouveler l'abonnement
     */
    public function renew(): void
    {
        $newExpiry = $this->expires_at && $this->expires_at->isFuture()
            ? $this->expires_at->addMonth()
            : now()->addMonth();

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'expires_at' => $newExpiry,
        ]);
    }

    /**
     * Marquer comme expiré
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);
    }

    /**
     * Vérifier si un abonnement actif existe pour une conversation
     */
    public static function hasActiveForConversation(int $subscriberId, int $conversationId): bool
    {
        return self::where('subscriber_id', $subscriberId)
            ->where('conversation_id', $conversationId)
            ->active()
            ->exists();
    }

    /**
     * Vérifier si un abonnement actif existe pour un message
     */
    public static function hasActiveForMessage(int $subscriberId, int $messageId): bool
    {
        return self::where('subscriber_id', $subscriberId)
            ->where('message_id', $messageId)
            ->active()
            ->exists();
    }
}
