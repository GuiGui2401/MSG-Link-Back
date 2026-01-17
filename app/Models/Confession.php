<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\Encryptable;
use App\Traits\Reportable;

class Confession extends Model
{
    use HasFactory, SoftDeletes, Encryptable, Reportable;

    protected $fillable = [
        'author_id',
        'recipient_id',
        'content',
        'image',
        'video',
        'type',
        'status',
        'moderated_by',
        'moderated_at',
        'rejection_reason',
        'is_identity_revealed',
        'revealed_at',
        'likes_count',
        'views_count',
        'is_anonymous',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
        'is_identity_revealed' => 'boolean',
        'revealed_at' => 'datetime',
        'likes_count' => 'integer',
        'views_count' => 'integer',
        'is_anonymous' => 'boolean',
    ];

    /**
     * Champs à chiffrer
     */
    protected $encryptable = ['content'];

    /**
     * Types de confessions
     */
    const TYPE_PRIVATE = 'private';
    const TYPE_PUBLIC = 'public';

    /**
     * Statuts de modération
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // ==================== RELATIONS ====================

    /**
     * Auteur de la confession
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Destinataire de la confession (pour les privées)
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Modérateur qui a traité la confession
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Utilisateurs qui ont liké la confession
     */
    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'confession_likes')
            ->withTimestamps();
    }

    /**
     * Commentaires de la confession
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ConfessionComment::class);
    }

    /**
     * Promotions de la confession
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(PostPromotion::class);
    }

    /**
     * Get active promotion
     */
    public function activePromotion(): ?PostPromotion
    {
        return $this->promotions()
            ->where('status', PostPromotion::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->first();
    }

    /**
     * Check if post is promoted
     */
    public function isPromoted(): bool
    {
        return $this->activePromotion() !== null;
    }

    // ==================== ACCESSORS ====================

    /**
     * Initiale de l'auteur
     */
    public function getAuthorInitialAttribute(): string
    {
        return $this->author->initial ?? '?';
    }

    /**
     * URL complète de l'image
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Si c'est déjà une URL complète
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

    /**
     * URL complète de la vidéo
     */
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video) {
            return null;
        }

        // Si c'est déjà une URL complète
        if (str_starts_with($this->video, 'http')) {
            return $this->video;
        }

        return asset('storage/' . $this->video);
    }

    /**
     * La confession est-elle publique ?
     */
    public function getIsPublicAttribute(): bool
    {
        return $this->type === self::TYPE_PUBLIC;
    }

    /**
     * La confession est-elle privée ?
     */
    public function getIsPrivateAttribute(): bool
    {
        return $this->type === self::TYPE_PRIVATE;
    }

    /**
     * La confession est-elle en attente de modération ?
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * La confession est-elle approuvée ?
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Informations de l'auteur (si identité révélée)
     */
    public function getAuthorInfoAttribute(): ?array
    {
        if (!$this->is_identity_revealed) {
            return null;
        }

        return [
            'id' => $this->author->id,
            'username' => $this->author->username,
            'full_name' => $this->author->full_name,
            'avatar_url' => $this->author->avatar_url,
        ];
    }

    // ==================== SCOPES ====================

    /**
     * Confessions publiques approuvées
     */
    public function scopePublicApproved($query)
    {
        return $query->where('type', self::TYPE_PUBLIC)
            ->where('status', self::STATUS_APPROVED);
    }

    /**
     * Confessions publiques
     */
    public function scopePublic($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    /**
     * Confessions approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Confessions privées
     */
    public function scopePrivate($query)
    {
        return $query->where('type', self::TYPE_PRIVATE);
    }

    /**
     * Confessions en attente de modération
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Confessions pour un destinataire
     */
    public function scopeForRecipient($query, int $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    // ==================== METHODS ====================

    /**
     * Approuver la confession
     */
    public function approve(User $moderator): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Rejeter la confession
     */
    public function reject(User $moderator, string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Révéler l'identité de l'auteur
     */
    public function revealIdentity(): void
    {
        $this->update([
            'is_identity_revealed' => true,
            'revealed_at' => now(),
        ]);
    }

    /**
     * Incrémenter le compteur de vues (une seule fois par utilisateur)
     */
    public function incrementViews(?User $viewer = null): void
    {
        // Ne pas compter les vues pour les utilisateurs non connectés
        if (!$viewer) {
            return;
        }

        // Vérifier si l'utilisateur a déjà vu cette confession
        $alreadyViewed = DB::table('confession_views')
            ->where('confession_id', $this->id)
            ->where('user_id', $viewer->id)
            ->exists();

        if ($alreadyViewed) {
            return;
        }

        // Enregistrer la vue
        DB::table('confession_views')->insert([
            'confession_id' => $this->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Incrémenter le compteur
        $this->increment('views_count');
    }

    /**
     * Liker la confession
     */
    public function like(User $user): bool
    {
        if ($this->likedBy()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $this->likedBy()->attach($user->id);
        $this->increment('likes_count');

        return true;
    }

    /**
     * Unliker la confession
     */
    public function unlike(User $user): bool
    {
        if (!$this->likedBy()->where('user_id', $user->id)->exists()) {
            return false;
        }

        $this->likedBy()->detach($user->id);
        $this->decrement('likes_count');

        return true;
    }

    /**
     * Vérifier si un utilisateur a liké
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likedBy()->where('user_id', $user->id)->exists();
    }
}
