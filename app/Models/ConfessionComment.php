<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Encryptable;

class ConfessionComment extends Model
{
    use HasFactory, SoftDeletes, Encryptable;

    protected $fillable = [
        'confession_id',
        'parent_id',
        'author_id',
        'content',
        'is_anonymous',
        'media_type',
        'media_url',
        'voice_type',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    /**
     * Champs à chiffrer
     */
    protected $encryptable = ['content'];

    // ==================== RELATIONS ====================

    /**
     * Confession commentée
     */
    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    /**
     * Auteur du commentaire
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Utilisateurs qui ont liké ce commentaire
     */
    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'confession_comment_likes', 'comment_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Commentaire parent (si c'est une réponse)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ConfessionComment::class, 'parent_id');
    }

    /**
     * Réponses à ce commentaire
     */
    public function replies()
    {
        return $this->hasMany(ConfessionComment::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    // ==================== ACCESSORS ====================

    /**
     * Nombre de likes
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likedBy()->count();
    }

    // ==================== METHODS ====================

    /**
     * Liker ce commentaire
     */
    public function like(User $user): bool
    {
        if ($this->isLikedBy($user)) {
            return false;
        }

        $this->likedBy()->attach($user->id);
        return true;
    }

    /**
     * Unliker ce commentaire
     */
    public function unlike(User $user): bool
    {
        if (!$this->isLikedBy($user)) {
            return false;
        }

        $this->likedBy()->detach($user->id);
        return true;
    }

    /**
     * Vérifier si un utilisateur a liké ce commentaire
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likedBy()->where('user_id', $user->id)->exists();
    }

    // ==================== ACCESSORS (continued) ====================

    /**
     * Initiale de l'auteur
     */
    public function getAuthorInitialAttribute(): string
    {
        if ($this->is_anonymous) {
            return '?';
        }
        return $this->author->initial ?? '?';
    }

    /**
     * Nom de l'auteur (anonyme ou non)
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonyme';
        }
        return $this->author->first_name ?? 'Utilisateur';
    }
}
