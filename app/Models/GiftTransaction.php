<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class GiftTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_id',
        'sender_id',
        'recipient_id',
        'conversation_id',
        'amount',
        'platform_fee',
        'net_amount',
        'status',
        'payment_reference',
        'message',
        'is_anonymous',
    ];

    protected $casts = [
        'amount' => 'integer',
        'platform_fee' => 'integer',
        'net_amount' => 'integer',
        'is_anonymous' => 'boolean',
    ];

    /**
     * Pourcentage de commission plateforme (5%)
     */
    const PLATFORM_FEE_PERCENT = 5;

    /**
     * Statuts possibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    // ==================== RELATIONS ====================

    /**
     * Cadeau envoyé
     */
    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    /**
     * Expéditeur
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Destinataire
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Conversation associée
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Message de chat associé
     */
    public function chatMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'gift_transaction_id');
    }

    /**
     * Transaction wallet associée
     */
    public function walletTransaction(): MorphOne
    {
        return $this->morphOne(WalletTransaction::class, 'transactionable');
    }

    // ==================== ACCESSORS ====================

    /**
     * Montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Transaction complétée ?
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    // ==================== SCOPES ====================

    /**
     * Transactions complétées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Transactions en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Par expéditeur
     */
    public function scopeBySender($query, int $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Par destinataire
     */
    public function scopeByRecipient($query, int $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    // ==================== METHODS ====================

    /**
     * Calculer les montants (appelé avant la création)
     */
    public static function calculateAmounts(int $price): array
    {
        $platformFee = (int) ceil($price * self::PLATFORM_FEE_PERCENT / 100);
        $netAmount = $price - $platformFee;

        return [
            'amount' => $price,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Marquer comme complété
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);

        // Créditer le wallet du destinataire
        $this->recipient->creditWallet(
            $this->net_amount,
            "Cadeau reçu : {$this->gift->name}",
            $this
        );
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Rembourser
     */
    public function refund(): void
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return;
        }

        $this->update(['status' => self::STATUS_REFUNDED]);

        // Débiter le wallet du destinataire si déjà crédité
        $this->recipient->debitWallet(
            $this->net_amount,
            "Remboursement cadeau : {$this->gift->name}",
            $this
        );
    }
}
