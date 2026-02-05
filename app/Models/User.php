<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasWallet;
use App\Traits\Reportable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasWallet, HasRoles, Reportable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'original_pin',
        'avatar',
        'cover_image',
        'bio',
        'is_verified',
        'is_premium',
        'premium_started_at',
        'premium_expires_at',
        'premium_auto_renew',
        'is_banned',
        'banned_reason',
        'banned_at',
        'wallet_balance',
        'last_seen_at',
        'settings',
        'role',
        'is_system_user',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'original_pin',
        'remember_token',
        'fcm_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_premium' => 'boolean',
        'premium_started_at' => 'datetime',
        'premium_expires_at' => 'datetime',
        'premium_auto_renew' => 'boolean',
        'is_banned' => 'boolean',
        'banned_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'settings' => 'array',
        'wallet_balance' => 'decimal:2',
        'promotion_balance' => 'decimal:2',
        'is_system_user' => 'boolean',
    ];

    /**
     * Attributs par défaut
     */
    protected $attributes = [
        'settings' => '{"notifications": true, "dark_mode": "auto", "language": "fr"}',
    ];

    // ==================== ACCESSORS ====================

    /**
     * Nom complet de l'utilisateur
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Initiale de l'utilisateur (pour affichage anonyme)
     */
    public function getInitialAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1));
    }

    /**
     * URL du profil public
     */
    public function getProfileUrlAttribute(): string
    {
        return config('app.frontend_url') . '/u/' . $this->username;
    }

    /**
     * URL de l'avatar ou avatar par défaut
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=random';
    }

    /**
     * URL de l'image de couverture
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return null;
    }

    /**
     * Vérifier si l'utilisateur est superadmin
     */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Vérifier si l'utilisateur est admin (ou superadmin)
     */
    public function getIsAdminAttribute(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    /**
     * Vérifier si l'utilisateur est modérateur, admin ou superadmin
     */
    public function getIsModeratorAttribute(): bool
    {
        return in_array($this->role, ['moderator', 'admin', 'superadmin']);
    }

    /**
     * Vérifier si l'utilisateur peut gérer un autre utilisateur
     */
    public function canManage(User $user): bool
    {
        // Personne ne peut gérer un utilisateur système sauf lui-même
        if ($user->is_system_user && !$this->is_system_user) {
            return false;
        }

        // Un utilisateur système peut tout gérer
        if ($this->is_system_user) {
            return $this->id !== $user->id;
        }

        // Un superadmin peut gérer tout le monde sauf lui-même
        if ($this->is_super_admin) {
            return $this->id !== $user->id;
        }

        // Un admin peut gérer les moderators et users, mais pas les admins/superadmins
        if ($this->role === 'admin') {
            return in_array($user->role, ['moderator', 'user']);
        }

        // Un moderator ne peut gérer que les users
        if ($this->role === 'moderator') {
            return $user->role === 'user';
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur peut bannir un autre utilisateur
     */
    public function canBan(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Obtenir le label du rôle
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'superadmin' => 'Super Admin',
            'admin' => 'Administrateur',
            'moderator' => 'Modérateur',
            default => 'Utilisateur',
        };
    }

    /**
     * Vérifier si l'utilisateur est en ligne (actif dans les 5 dernières minutes)
     */
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }
        return $this->last_seen_at->diffInMinutes(now()) < 5;
    }

    /**
     * Vérifier si l'utilisateur a un passe premium actif
     */
    public function getHasActivePremiumAttribute(): bool
    {
        return $this->is_premium
            && $this->premium_expires_at
            && $this->premium_expires_at->isFuture();
    }

    /**
     * Jours restants du passe premium
     */
    public function getPremiumDaysRemainingAttribute(): int
    {
        if (!$this->has_active_premium || !$this->premium_expires_at) {
            return 0;
        }
        return (int) now()->diffInDays($this->premium_expires_at);
    }

    // ==================== RELATIONS ====================

    /**
     * Messages anonymes envoyés
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(AnonymousMessage::class, 'sender_id');
    }

    /**
     * Messages anonymes reçus
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(AnonymousMessage::class, 'recipient_id');
    }

    /**
     * Confessions écrites
     */
    public function confessionsWritten(): HasMany
    {
        return $this->hasMany(Confession::class, 'author_id');
    }

    /**
     * Confessions reçues
     */
    public function confessionsReceived(): HasMany
    {
        return $this->hasMany(Confession::class, 'recipient_id');
    }

    /**
     * Conversations (comme participant 1 ou 2)
     */
    public function conversations()
    {
        return Conversation::where('participant_one_id', $this->id)
            ->orWhere('participant_two_id', $this->id);
    }

    /**
     * Messages de chat envoyés
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /**
     * Cadeaux envoyés
     */
    public function giftsSent(): HasMany
    {
        return $this->hasMany(GiftTransaction::class, 'sender_id');
    }

    /**
     * Cadeaux reçus
     */
    public function giftsReceived(): HasMany
    {
        return $this->hasMany(GiftTransaction::class, 'recipient_id');
    }

    /**
     * Abonnements premium souscrits
     */
    public function premiumSubscriptions(): HasMany
    {
        return $this->hasMany(PremiumSubscription::class, 'subscriber_id');
    }

    /**
     * Abonnements premium dont je suis la cible
     */
    public function premiumSubscribers(): HasMany
    {
        return $this->hasMany(PremiumSubscription::class, 'target_user_id');
    }

    /**
     * Passes premium de l'utilisateur
     */
    public function premiumPasses(): HasMany
    {
        return $this->hasMany(PremiumPass::class);
    }

    /**
     * Passe premium actif
     */
    public function activePremiumPass(): HasMany
    {
        return $this->hasMany(PremiumPass::class)
            ->where('status', PremiumPass::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Paiements effectués
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Demandes de retrait
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Transactions wallet
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Transactions CinetPay (dépôts, etc.)
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Utilisateurs bloqués
     */
    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id')
            ->withTimestamps();
    }

    /**
     * Utilisateurs qui m'ont bloqué
     */
    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'blocker_id')
            ->withTimestamps();
    }

    /**
     * Signalements effectués
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Codes de vérification
     */
    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    /**
     * Stories créées
     */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Stories actives
     */
    public function activeStories(): HasMany
    {
        return $this->hasMany(Story::class)
            ->where('status', Story::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Users that this user is following
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Users that follow this user
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Post promotions
     */
    public function postPromotions(): HasMany
    {
        return $this->hasMany(PostPromotion::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope pour les utilisateurs actifs (non bannis)
     */
    public function scopeActive($query)
    {
        return $query->where('is_banned', false);
    }

    /**
     * Scope pour les utilisateurs bannis
     */
    public function scopeBanned($query)
    {
        return $query->where('is_banned', true);
    }

    /**
     * Scope pour les admins
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope pour exclure les utilisateurs système
     * Ces utilisateurs sont invisibles pour les autres admins/superadmins
     */
    public function scopeExcludeSystemUsers($query)
    {
        return $query->where(function ($q) {
            $q->where('is_system_user', false)
              ->orWhereNull('is_system_user');
        });
    }

    /**
     * Scope pour les utilisateurs visibles dans l'admin
     * Exclut les utilisateurs système sauf pour les utilisateurs système eux-mêmes
     */
    public function scopeVisibleInAdmin($query)
    {
        $currentUser = auth()->user();

        // Si l'utilisateur actuel est un utilisateur système, il peut tout voir
        if ($currentUser && $currentUser->is_system_user) {
            return $query;
        }

        // Sinon, exclure les utilisateurs système
        return $query->excludeSystemUsers();
    }

    /**
     * Scope pour recherche
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('username', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // ==================== METHODS ====================

    /**
     * Vérifier si l'utilisateur a bloqué un autre utilisateur
     */
    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
    }

    /**
     * Vérifier si l'utilisateur est bloqué par un autre utilisateur
     */
    public function isBlockedBy(User $user): bool
    {
        return $user->hasBlocked($this);
    }

    /**
     * Vérifier si l'utilisateur a un abonnement premium actif pour une conversation
     */
    public function hasPremiumFor(Conversation $conversation): bool
    {
        return $this->premiumSubscriptions()
            ->where('conversation_id', $conversation->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur a un abonnement premium actif pour un message
     */
    public function hasPremiumForMessage(AnonymousMessage $message): bool
    {
        return $this->premiumSubscriptions()
            ->where('message_id', $message->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur peut voir l'identité de tout le monde (passe premium global)
     */
    public function canViewAllIdentities(): bool
    {
        return $this->has_active_premium;
    }

    /**
     * Obtenir la conversation avec un autre utilisateur
     */
    public function getConversationWith(User $user): ?Conversation
    {
        return Conversation::between($this->id, $user->id)->first();
    }

    /**
     * Créer ou obtenir une conversation avec un autre utilisateur
     */
    public function getOrCreateConversationWith(User $user): Conversation
    {
        $conversation = $this->getConversationWith($user);

        if (!$conversation) {
            $conversation = Conversation::create([
                'participant_one_id' => min($this->id, $user->id),
                'participant_two_id' => max($this->id, $user->id),
            ]);
        }

        return $conversation;
    }

    /**
     * Bannir l'utilisateur
     */
    public function ban(string $reason = null): void
    {
        $this->update([
            'is_banned' => true,
            'banned_reason' => $reason,
            'banned_at' => now(),
        ]);

        // Révoquer tous les tokens
        $this->tokens()->delete();
    }

    /**
     * Débannir l'utilisateur
     */
    public function unban(): void
    {
        $this->update([
            'is_banned' => false,
            'banned_reason' => null,
            'banned_at' => null,
        ]);
    }

    /**
     * Mettre à jour le dernier vu
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Follow a user
     */
    public function follow(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        if (!$this->isFollowing($user)) {
            $this->following()->attach($user->id);
            return true;
        }

        return false;
    }

    /**
     * Unfollow a user
     */
    public function unfollow(User $user): bool
    {
        if ($this->isFollowing($user)) {
            $this->following()->detach($user->id);
            return true;
        }

        return false;
    }

    /**
     * Check if following a user
     */
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    /**
     * Check if followed by a user
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    /**
     * Get followers count
     */
    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    /**
     * Get following count
     */
    public function getFollowingCountAttribute(): int
    {
        return $this->following()->count();
    }

    /**
     * Générer un username unique
     */
    public static function generateUsername(string $firstName, string $lastName): string
    {
        // Translittérer les caractères accentués en ASCII
        $fullName = $firstName . $lastName;

        // Remplacer les caractères accentués courants
        $transliterations = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
            'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
        ];

        $fullName = strtr($fullName, $transliterations);

        // Supprimer les caractères restants qui ne sont pas alphanumériques
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullName));

        // S'assurer que le username a au moins 3 caractères
        if (strlen($base) < 3) {
            $base = $base . 'user' . rand(100, 999);
        }

        $username = $base;
        $counter = 1;

        while (self::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
