<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sponsorship extends Model
{
    use HasFactory;

    const MEDIA_TEXT = 'text';
    const MEDIA_IMAGE = 'image';
    const MEDIA_VIDEO = 'video';

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'sponsorship_package_id',
        'media_type',
        'text_content',
        'media_url',
        'price',
        'reach_min',
        'reach_max',
        'duration_days',
        'ends_at',
        'status',
        'delivered_count',
    ];

    protected $casts = [
        'price' => 'integer',
        'reach_min' => 'integer',
        'reach_max' => 'integer',
        'delivered_count' => 'integer',
        'duration_days' => 'integer',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SponsorshipPackage::class, 'sponsorship_package_id');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(SponsorshipImpression::class);
    }

    public function getTargetReachAttribute(): int
    {
        return $this->reach_max ?: $this->reach_min;
    }
}
