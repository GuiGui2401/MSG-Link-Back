<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    protected $fillable = [
        'viewer_id',
        'viewed_id',
    ];

    /**
     * Get the user who viewed the profile
     */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * Get the user whose profile was viewed
     */
    public function viewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewed_id');
    }
}
