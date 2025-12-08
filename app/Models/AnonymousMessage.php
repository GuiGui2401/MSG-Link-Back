<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Encryptable;
use App\Traits\Reportable;

class AnonymousMessage extends Model
{
    use HasFactory, SoftDeletes, Encryptable, Reportable;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'content',
        'is_read',
        'read_at',
        'is_identity_revealed',
        'revealed_at',
        'revealed_via_subscription_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_identity_revealed' => 'boolean',
        'revealed_at' => 'datetime',
    ];

    /**
     * Champs à chiffrer
     */
    protected $encryptable = ['content'];

    // ==================== RELATIONS ====================

    /**
     * Expéditeur du message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Destinataire du message
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Abonnement qui a révélé l'identité
     */
    public function revealedViaSubscription(): BelongsTo
    {
        return $this->belongsTo(PremiumSubscription::class, 'revealed_via_subscription_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Initiale de l'expéditeur (toujours visible)
     */
    public function getSenderInitialAttribute(): string
    {
        return $this->sender->initial ?? '?';
    }

    /**
     * Informations de l'expéditeur (visible si identité révélée)
     */
    public function getSenderInfoAttribute(): ?array
    {
        if (!$this->is_identity_revealed) {
            return null;
        }

        return [
            'id' => $this->sender->id,
            'username' => $this->sender->username,
            'full_name' => $this->sender->full_name,
            'avatar_url' => $this->sender->avatar_url,
        ];
    }

    // ==================== SCOPES ====================

    /**
     * Messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Messages lus
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Messages pour un destinataire
     */
    public function scopeForRecipient($query, int $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Messages d'un expéditeur
     */
    public function scopeFromSender($query, int $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    // ==================== METHODS ====================

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Révéler l'identité
     */
    public function revealIdentity(?PremiumSubscription $subscription = null): void
    {
        $this->update([
            'is_identity_revealed' => true,
            'revealed_at' => now(),
            'revealed_via_subscription_id' => $subscription?->id,
        ]);
    }

    /**
     * Vérifier si le destinataire peut voir l'identité
     */
    public function canRevealIdentity(User $user): bool
    {
        // Seul le destinataire peut révéler l'identité
        if ($this->recipient_id !== $user->id) {
            return false;
        }

        // Déjà révélé
        if ($this->is_identity_revealed) {
            return false;
        }

        return true;
    }
}
