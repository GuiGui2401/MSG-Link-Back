<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonetizationPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'period_start',
        'period_end',
        'views_count',
        'likes_count',
        'engagement_score',
        'total_engagement_score',
        'amount',
        'status',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'engagement_score' => 'integer',
        'total_engagement_score' => 'integer',
        'amount' => 'integer',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    const TYPE_CREATOR_FUND = 'creator_fund';
    const TYPE_AD_REVENUE = 'ad_revenue';

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_SKIPPED = 'skipped';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
