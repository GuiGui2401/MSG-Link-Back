<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoryComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'story_id',
        'user_id',
        'content',
        'parent_id',
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    // ==================== RELATIONS ====================

    /**
     * La story commentée
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * L'auteur du commentaire
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Commentaire parent (pour les réponses)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(StoryComment::class, 'parent_id');
    }

    /**
     * Réponses à ce commentaire
     */
    public function replies(): HasMany
    {
        return $this->hasMany(StoryComment::class, 'parent_id');
    }
}
