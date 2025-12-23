<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'last_message_at',
        'streak_count',
        'streak_updated_at',
        'flame_level',
        'message_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'streak_updated_at' => 'datetime',
        'streak_count' => 'integer',
        'message_count' => 'integer',
    ];

    /**
     * Niveaux de flame
     */
    const FLAME_NONE = 'none';
    const FLAME_YELLOW = 'yellow';      // 2 jours
    const FLAME_ORANGE = 'orange';      // 7 jours
    const FLAME_PURPLE = 'purple';      // 30 jours

    // ==================== RELATIONS ====================

    /**
     * Premier participant
     */
    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one_id')->withoutTrashed();
    }

    /**
     * Second participant
     */
    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two_id')->withoutTrashed();
    }

    /**
     * Messages de la conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Dernier message
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    /**
     * Abonnements premium associés
     */
    public function premiumSubscriptions(): HasMany
    {
        return $this->hasMany(PremiumSubscription::class);
    }

    /**
     * Transactions de cadeaux
     */
    public function giftTransactions(): HasMany
    {
        return $this->hasMany(GiftTransaction::class);
    }

    /**
     * Révélations d'identité dans cette conversation
     */
    public function identityReveals(): HasMany
    {
        return $this->hasMany(ConversationIdentityReveal::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Emoji de la flame selon le niveau
     */
    public function getFlameEmojiAttribute(): string
    {
        return match ($this->flame_level) {
            self::FLAME_YELLOW => '🔥',
            self::FLAME_ORANGE => '🔥',
            self::FLAME_PURPLE => '💜🔥',
            default => '',
        };
    }

    /**
     * Couleur de la flame pour le frontend
     */
    public function getFlameColorAttribute(): ?string
    {
        return match ($this->flame_level) {
            self::FLAME_YELLOW => '#FFD700',
            self::FLAME_ORANGE => '#FF6B35',
            self::FLAME_PURPLE => '#9B59B6',
            default => null,
        };
    }

    // ==================== SCOPES ====================

    /**
     * Conversations entre deux utilisateurs
     */
    public function scopeBetween($query, int $userId1, int $userId2)
    {
        $minId = min($userId1, $userId2);
        $maxId = max($userId1, $userId2);

        return $query->where('participant_one_id', $minId)
            ->where('participant_two_id', $maxId);
    }

    /**
     * Conversations d'un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('participant_one_id', $userId)
            ->orWhere('participant_two_id', $userId);
    }

    /**
     * Conversations avec des messages récents
     */
    public function scopeWithRecentActivity($query)
    {
        return $query->whereNotNull('last_message_at')
            ->orderBy('last_message_at', 'desc');
    }

    /**
     * Conversations avec streak actif
     */
    public function scopeWithStreak($query)
    {
        return $query->where('streak_count', '>', 0);
    }

    // ==================== METHODS ====================

    /**
     * Obtenir l'autre participant
     */
    public function getOtherParticipant(User $user): ?User
    {
        return $this->participant_one_id === $user->id
            ? $this->participantTwo
            : $this->participantOne;
    }

    /**
     * Vérifier si un utilisateur est participant
     */
    public function hasParticipant(User $user): bool
    {
        return $this->participant_one_id === $user->id
            || $this->participant_two_id === $user->id;
    }

    /**
     * Mettre à jour après un nouveau message
     */
    public function updateAfterMessage(): void
    {
        $this->increment('message_count');
        $this->update(['last_message_at' => now()]);
        $this->updateStreak();
    }

    /**
     * Mettre à jour le streak
     */
    public function updateStreak(): void
    {
        $now = now();

        // Si pas de streak précédent ou streak expiré (plus de 24h sans message)
        if (!$this->streak_updated_at || $this->streak_updated_at->diffInHours($now) > 24) {
            // Vérifier si les deux participants ont envoyé un message aujourd'hui
            $todayStart = $now->copy()->startOfDay();

            $participantOneMessaged = $this->messages()
                ->where('sender_id', $this->participant_one_id)
                ->where('created_at', '>=', $todayStart)
                ->exists();

            $participantTwoMessaged = $this->messages()
                ->where('sender_id', $this->participant_two_id)
                ->where('created_at', '>=', $todayStart)
                ->exists();

            if ($participantOneMessaged && $participantTwoMessaged) {
                $this->increment('streak_count');
                $this->update([
                    'streak_updated_at' => $now,
                    'flame_level' => $this->calculateFlameLevel($this->streak_count + 1),
                ]);
            }
        }
    }

    /**
     * Calculer le niveau de flame
     */
    protected function calculateFlameLevel(int $streakCount): string
    {
        return match (true) {
            $streakCount >= 30 => self::FLAME_PURPLE,
            $streakCount >= 7 => self::FLAME_ORANGE,
            $streakCount >= 2 => self::FLAME_YELLOW,
            default => self::FLAME_NONE,
        };
    }

    /**
     * Réinitialiser le streak (appelé si le streak expire)
     */
    public function resetStreak(): void
    {
        $this->update([
            'streak_count' => 0,
            'flame_level' => self::FLAME_NONE,
            'streak_updated_at' => null,
        ]);
    }

    /**
     * Vérifier si un utilisateur a un abonnement premium actif
     */
    public function hasPremiumSubscription(User $user): bool
    {
        return $this->premiumSubscriptions()
            ->where('subscriber_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Compter les messages non lus pour un utilisateur
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Marquer tous les messages comme lus pour un utilisateur
     */
    public function markAllAsReadFor(User $user): void
    {
        $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Vérifier si un utilisateur a révélé l'identité de l'autre participant
     */
    public function hasRevealedIdentityFor(User $user, User $otherUser): bool
    {
        return $this->identityReveals()
            ->where('user_id', $user->id)
            ->where('revealed_user_id', $otherUser->id)
            ->exists();
    }

    /**
     * Vérifier si l'identité est révélée pour cet utilisateur
     * (soit il a payé dans Chat, soit il est premium, soit il a révélé un message de cette personne)
     */
    public function isIdentityRevealedFor(User $user): bool
    {
        $otherUser = $this->getOtherParticipant($user);

        // Si l'utilisateur est premium, il voit tout
        if ($user->is_premium) {
            return true;
        }

        // Vérifier si l'utilisateur a payé pour révéler l'identité dans le chat
        if ($this->hasRevealedIdentityFor($user, $otherUser)) {
            return true;
        }

        // Vérifier aussi si un message anonyme de cet utilisateur a été révélé (synchronisation Messages ↔ Chat)
        $hasRevealedMessage = \App\Models\AnonymousMessage::where('recipient_id', $user->id)
            ->where('sender_id', $otherUser->id)
            ->where('is_identity_revealed', true)
            ->exists();

        return $hasRevealedMessage;
    }
}
