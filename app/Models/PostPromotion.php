<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPromotion extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'confession_id',
        'user_id',
        'amount',
        'duration_hours',
        'reach_boost',
        'starts_at',
        'ends_at',
        'status',
        'impressions',
        'clicks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * The confession being promoted
     */
    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    /**
     * The user who promoted
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if promotion is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->ends_at->isFuture();
    }

    /**
     * Increment impressions
     */
    public function incrementImpressions(): void
    {
        $this->increment('impressions');
    }

    /**
     * Increment clicks
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '>', now());
    }

    /**
     * Scope for expired promotions that need status update
     */
    public function scopeExpiredNeedingUpdate($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '<=', now());
    }
}
