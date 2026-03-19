<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'username',
        'reason',
        'status',
        'ip_address',
        'user_agent',
        'scheduled_deletion_date',
        'processed_at',
        'processed_by',
        'cancelled_at',
        'admin_notes',
    ];

    protected $casts = [
        'scheduled_deletion_date' => 'datetime',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les différents statuts possibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'admin qui a traité la demande
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope pour les demandes en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les demandes en cours de traitement
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope pour les demandes complétées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope pour les demandes annulées
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope pour les demandes rejetées
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Vérifier si la demande est en attente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si la demande est en cours de traitement
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Vérifier si la demande est complétée
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Vérifier si la demande est annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Vérifier si la demande est rejetée
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Marquer comme en cours de traitement
     */
    public function markAsProcessing(int $adminId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_by' => $adminId,
        ]);
    }

    /**
     * Marquer comme complété
     */
    public function markAsCompleted(int $adminId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'processed_by' => $adminId,
        ]);
    }

    /**
     * Marquer comme annulé
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Marquer comme rejeté
     */
    public function markAsRejected(int $adminId = null, string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_at' => now(),
            'processed_by' => $adminId,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Planifier la suppression
     */
    public function scheduleDeletion(\DateTime $date): bool
    {
        return $this->update([
            'scheduled_deletion_date' => $date,
        ]);
    }
}
