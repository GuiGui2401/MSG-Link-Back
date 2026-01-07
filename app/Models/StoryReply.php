<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryReply extends Model
{
    const TYPE_TEXT = 'text';
    const TYPE_VOICE = 'voice';
    const TYPE_IMAGE = 'image';
    const TYPE_EMOJI = 'emoji';

    const VOICE_EFFECTS = ['pitch_up', 'pitch_down', 'robot', 'chipmunk', 'deep'];

    protected $fillable = [
        'story_id',
        'user_id',
        'content',
        'type',
        'media_url',
        'voice_effect',
        'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    /**
     * The story this reply belongs to
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * The user who replied
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the media URL attribute
     */
    public function getMediaFullUrlAttribute(): ?string
    {
        if (!$this->media_url) {
            return null;
        }
        return asset('storage/' . $this->media_url);
    }
}
