<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_TEXT = 'text';

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'media_url',
        'content',
        'thumbnail_url',
        'background_color',
        'duration',
        'views_count',
        'status',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'duration' => 'integer',
        'views_count' => 'integer',
    ];

    // ==================== ACCESSORS ====================

    /**
     * URL complète du média
     */
    public function getMediaFullUrlAttribute(): ?string
    {
        if (!$this->media_url) {
            return null;
        }
        return url('storage/' . $this->media_url);
    }

    /**
     * URL complète de la miniature
     */
    public function getThumbnailFullUrlAttribute(): ?string
    {
        if (!$this->thumbnail_url) {
            return null;
        }
        return url('storage/' . $this->thumbnail_url);
    }

    /**
     * Vérifier si la story est expirée
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast() || $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Vérifier si la story est active
     */
    public function getIsActiveAttribute(): bool
    {
        return !$this->is_expired && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Temps restant avant expiration (en secondes)
     */
    public function getTimeRemainingAttribute(): int
    {
        if ($this->is_expired) {
            return 0;
        }
        return max(0, $this->expires_at->diffInSeconds(now()));
    }

    // ==================== RELATIONS ====================

    /**
     * Utilisateur propriétaire de la story
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutTrashed();
    }

    /**
     * Utilisateurs qui ont vu la story
     */
    public function viewedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'story_views')
            ->withTimestamps();
    }

    /**
     * Réponses à la story
     */
    public function replies(): HasMany
    {
        return $this->hasMany(StoryReply::class);
    }

    /**
     * Check if story is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    // ==================== SCOPES ====================

    /**
     * Scope pour les stories actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope pour les stories expirées
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Scope pour les stories d'un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les stories des utilisateurs suivis (à implémenter si système de follow)
     */
    public function scopeFromFollowing($query, User $user)
    {
        // Pour l'instant, retourne toutes les stories actives
        // À adapter selon le système de follow/friends
        return $query->active()
            ->with('user:id,first_name,last_name,username,avatar')
            ->orderBy('created_at', 'desc');
    }

    // ==================== METHODS ====================

    /**
     * Incrémenter le nombre de vues
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Marquer la story comme vue par un utilisateur
     */
    public function markAsViewedBy(User $user): bool
    {
        if ($this->isViewedBy($user)) {
            return false;
        }

        $this->viewedBy()->attach($user->id);
        $this->incrementViews();

        return true;
    }

    /**
     * Vérifier si un utilisateur a vu la story
     */
    public function isViewedBy(User $user): bool
    {
        return $this->viewedBy()->where('user_id', $user->id)->exists();
    }

    /**
     * Marquer la story comme expirée
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Supprimer les stories expirées (à exécuter via un cron job)
     */
    public static function deleteExpiredStories(): int
    {
        return self::expired()->delete();
    }

    /**
     * Marquer automatiquement comme expirée les stories dont la date est dépassée
     */
    public static function markExpiredStories(): int
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Obtenir les utilisateurs qui ont vu la story avec leurs infos
     */
    public function getViewers()
    {
        return $this->viewedBy()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.username', 'users.avatar')
            ->withPivot('created_at')
            ->orderBy('story_views.created_at', 'desc')
            ->get();
    }
}
