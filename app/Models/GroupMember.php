<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'is_muted',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
    ];

    // ==================== CONSTANTS ====================

    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_MEMBER = 'member';

    // ==================== RELATIONS ====================

    /**
     * Groupe auquel appartient le membre
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Utilisateur membre
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Nom d'affichage anonyme (nom généré aléatoire)
     */
    public function getAnonymousNameAttribute(): string
    {
        // Liste d'animaux pour générer des noms anonymes
        $animals = [
            'Panda', 'Licorne', 'Dragon', 'Phoenix', 'Tigre', 'Lion',
            'Aigle', 'Dauphin', 'Loup', 'Renard', 'Chat', 'Hibou',
            'Koala', 'Ours', 'Cerf', 'Lynx', 'Faucon', 'Cobra',
            'Léopard', 'Jaguar', 'Panthère', 'Guépard'
        ];

        // Générer un nom basé sur l'ID du membre pour consistance
        $index = $this->id % count($animals);
        $number = ($this->id * 7) % 100; // Un nombre pseudo-aléatoire basé sur l'ID

        return $animals[$index] . $number;
    }

    /**
     * Vérifier si c'est un admin
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Vérifier si c'est un modérateur
     */
    public function getIsModeratorAttribute(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    // ==================== SCOPES ====================

    /**
     * Membres actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Admins uniquement
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Modérateurs uniquement
     */
    public function scopeModerators($query)
    {
        return $query->where('role', self::ROLE_MODERATOR);
    }

    /**
     * Membres réguliers uniquement
     */
    public function scopeRegularMembers($query)
    {
        return $query->where('role', self::ROLE_MEMBER);
    }

    // ==================== METHODS ====================

    /**
     * Mettre à jour la dernière lecture
     */
    public function updateLastRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    /**
     * Activer/désactiver le mode muet
     */
    public function toggleMute(): void
    {
        $this->update(['is_muted' => !$this->is_muted]);
    }

    /**
     * Promouvoir le membre
     */
    public function promote(): bool
    {
        if ($this->role === self::ROLE_MEMBER) {
            $this->update(['role' => self::ROLE_MODERATOR]);
            return true;
        }

        if ($this->role === self::ROLE_MODERATOR) {
            $this->update(['role' => self::ROLE_ADMIN]);
            return true;
        }

        return false;
    }

    /**
     * Rétrograder le membre
     */
    public function demote(): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            $this->update(['role' => self::ROLE_MODERATOR]);
            return true;
        }

        if ($this->role === self::ROLE_MODERATOR) {
            $this->update(['role' => self::ROLE_MEMBER]);
            return true;
        }

        return false;
    }

    /**
     * Vérifier si le membre peut modérer
     */
    public function canModerate(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MODERATOR]);
    }

    /**
     * Compter les messages non lus
     */
    public function unreadMessagesCount(): int
    {
        return $this->group->messages()
            ->where('sender_id', '!=', $this->user_id)
            ->where('created_at', '>', $this->last_read_at ?? $this->joined_at)
            ->count();
    }
}
