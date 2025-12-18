<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'amount',
        'currency',
        'status',
        'reference',
        'provider_reference',
        'metadata',
        'failure_reason',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Types de paiement
     */
    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_GIFT = 'gift';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_DEPOSIT = 'deposit';

    /**
     * Providers
     */
    const PROVIDER_LIGOSAPP = 'ligosapp';
    const PROVIDER_CINETPAY = 'cinetpay';
    const PROVIDER_INTOUCH = 'intouch';
    const PROVIDER_MANUAL = 'manual';

    /**
     * Statuts
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference)) {
                $payment->reference = self::generateReference();
            }
        });
    }

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    // ==================== SCOPES ====================

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // ==================== METHODS ====================

    public static function generateReference(): string
    {
        return 'PAY-' . strtoupper(Str::random(16));
    }

    public function markAsCompleted(string $providerReference = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'provider_reference' => $providerReference ?? $this->provider_reference,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }
}
