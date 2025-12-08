<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCode extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'code',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Types de vérification
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';
    public const TYPE_PASSWORD_RESET = 'password_reset';

    // ==================== RELATIONS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getIsValidAttribute(): bool
    {
        return is_null($this->used_at) && $this->expires_at > now();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at <= now();
    }

    public function getIsUsedAttribute(): bool
    {
        return !is_null($this->used_at);
    }

    // ==================== METHODS ====================

    /**
     * Marquer le code comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * Générer un nouveau code de vérification
     */
    public static function generate(User $user, string $type): self
    {
        // Invalider les anciens codes
        static::forUser($user->id)
            ->ofType($type)
            ->valid()
            ->delete();

        $codeLength = config('msglink.security.verification_code_length', 6);
        $expiryMinutes = config('msglink.security.verification_code_expiry', 15);

        return static::create([
            'user_id' => $user->id,
            'type' => $type,
            'code' => str_pad(random_int(0, pow(10, $codeLength) - 1), $codeLength, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Vérifier un code
     */
    public static function verify(User $user, string $type, string $code): bool
    {
        $verificationCode = static::forUser($user->id)
            ->ofType($type)
            ->valid()
            ->where('code', $code)
            ->first();

        if (!$verificationCode) {
            return false;
        }

        $verificationCode->markAsUsed();

        return true;
    }
}
