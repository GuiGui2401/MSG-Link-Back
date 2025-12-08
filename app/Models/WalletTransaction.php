<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        'transactionable_type',
        'transactionable_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Types de transaction
     */
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphique vers la source (GiftTransaction, Withdrawal, etc.)
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    // ==================== ACCESSORS ====================

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->type === self::TYPE_CREDIT ? '+' : '-';
        return $prefix . number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    // ==================== SCOPES ====================

    public function scopeCredits($query)
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    public function scopeDebits($query)
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
