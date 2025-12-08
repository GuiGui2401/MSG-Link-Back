<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'fee',
        'net_amount',
        'phone_number',
        'provider',
        'status',
        'processed_by',
        'processed_at',
        'notes',
        'rejection_reason',
        'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'net_amount' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * Frais de retrait (peut être configuré)
     */
    const WITHDRAWAL_FEE = 0; // Pas de frais pour l'instant

    /**
     * Montant minimum de retrait
     */
    const MIN_WITHDRAWAL_AMOUNT = 1000;

    /**
     * Providers Mobile Money
     */
    const PROVIDER_MTN_MOMO = 'mtn_momo';
    const PROVIDER_ORANGE_MONEY = 'orange_money';
    const PROVIDER_OTHER = 'other';

    /**
     * Statuts
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REJECTED = 'rejected';

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function walletTransaction(): MorphOne
    {
        return $this->morphOne(WalletTransaction::class, 'transactionable');
    }

    // ==================== ACCESSORS ====================

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getProviderLabelAttribute(): string
    {
        return match ($this->provider) {
            self::PROVIDER_MTN_MOMO => 'MTN Mobile Money',
            self::PROVIDER_ORANGE_MONEY => 'Orange Money',
            default => 'Autre',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_REJECTED => 'Rejeté',
            default => $this->status,
        };
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==================== METHODS ====================

    /**
     * Calculer les montants
     */
    public static function calculateAmounts(int $amount): array
    {
        $fee = self::WITHDRAWAL_FEE;
        $netAmount = $amount - $fee;

        return [
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Traiter le retrait (approuver)
     */
    public function process(User $admin, string $transactionReference = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'transaction_reference' => $transactionReference,
        ]);

        // Débiter le wallet de l'utilisateur
        $this->user->debitWallet(
            $this->amount,
            'Retrait Mobile Money',
            $this
        );
    }

    /**
     * Rejeter le retrait
     */
    public function reject(User $admin, string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'rejection_reason' => $reason,
        ]);
    }
}
