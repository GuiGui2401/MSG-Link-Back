<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'payment_reference',
        'status',
        'starts_at',
        'expires_at',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Prix du passe premium (configurable via Settings)
     * Par défaut: 5000 FCFA/mois
     */
    const MONTHLY_PRICE = 5000;

    /**
     * Statuts possibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // ==================== RELATIONS ====================

    /**
     * Utilisateur propriétaire du passe premium
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Le passe est-il actif ?
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at
            && $this->expires_at->isFuture();
    }

    /**
     * Le passe est-il expiré ?
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Jours restants
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expires_at || $this->expires_at->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->expires_at);
    }

    /**
     * Montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    // ==================== SCOPES ====================

    /**
     * Passes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', now());
    }

    /**
     * Passes expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Passes expirant bientôt (dans les 3 prochains jours)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDays(3)]);
    }

    /**
     * Par utilisateur
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==================== METHODS ====================

    /**
     * Activer le passe premium
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        // Mettre à jour le statut premium de l'utilisateur
        $this->user->update([
            'is_premium' => true,
            'is_verified' => true, // Le passe premium rend le compte vérifié
            'premium_started_at' => now(),
            'premium_expires_at' => now()->addMonth(),
            'premium_auto_renew' => $this->auto_renew,
        ]);
    }

    /**
     * Renouveler le passe premium
     */
    public function renew(): void
    {
        $newExpiry = $this->expires_at && $this->expires_at->isFuture()
            ? $this->expires_at->addMonth()
            : now()->addMonth();

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'expires_at' => $newExpiry,
        ]);

        // Mettre à jour l'utilisateur
        $this->user->update([
            'is_premium' => true,
            'premium_expires_at' => $newExpiry,
        ]);
    }

    /**
     * Marquer comme expiré
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);

        // Mettre à jour l'utilisateur
        $this->user->update([
            'is_premium' => false,
            'premium_auto_renew' => false,
        ]);
    }

    /**
     * Annuler le passe premium
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);

        // Désactiver le renouvellement automatique
        $this->user->update([
            'premium_auto_renew' => false,
        ]);
    }

    /**
     * Vérifier si un utilisateur a un passe premium actif
     */
    public static function hasActive(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->active()
            ->exists();
    }

    /**
     * Obtenir le passe actif d'un utilisateur
     */
    public static function getActive(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->active()
            ->first();
    }

    /**
     * Générer une référence de paiement unique
     */
    public static function generatePaymentReference(): string
    {
        return 'PREMIUM_' . strtoupper(uniqid()) . '_' . now()->timestamp;
    }
}
