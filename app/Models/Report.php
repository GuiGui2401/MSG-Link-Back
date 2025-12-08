<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'action_taken',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Raisons de signalement
     */
    const REASON_SPAM = 'spam';
    const REASON_HARASSMENT = 'harassment';
    const REASON_HATE_SPEECH = 'hate_speech';
    const REASON_INAPPROPRIATE = 'inappropriate_content';
    const REASON_IMPERSONATION = 'impersonation';
    const REASON_OTHER = 'other';

    /**
     * Statuts
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    /**
     * Labels des raisons
     */
    public static function getReasonLabels(): array
    {
        return [
            self::REASON_SPAM => 'Spam',
            self::REASON_HARASSMENT => 'Harcèlement',
            self::REASON_HATE_SPEECH => 'Discours haineux',
            self::REASON_INAPPROPRIATE => 'Contenu inapproprié',
            self::REASON_IMPERSONATION => 'Usurpation d\'identité',
            self::REASON_OTHER => 'Autre',
        ];
    }

    // ==================== RELATIONS ====================

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ==================== ACCESSORS ====================

    public function getReasonLabelAttribute(): string
    {
        return self::getReasonLabels()[$this->reason] ?? $this->reason;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_REVIEWED => 'Examiné',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_DISMISSED => 'Rejeté',
            default => $this->status,
        };
    }

    public function getReportableTypeLabelAttribute(): string
    {
        return match ($this->reportable_type) {
            User::class => 'Utilisateur',
            AnonymousMessage::class => 'Message anonyme',
            Confession::class => 'Confession',
            ChatMessage::class => 'Message de chat',
            default => 'Contenu',
        };
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('reportable_type', $type);
    }

    // ==================== METHODS ====================

    /**
     * Résoudre le signalement
     */
    public function resolve(User $admin, string $actionTaken = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'action_taken' => $actionTaken,
        ]);
    }

    /**
     * Rejeter le signalement
     */
    public function dismiss(User $admin, string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'action_taken' => $reason ?? 'Signalement rejeté',
        ]);
    }

    /**
     * Marquer comme examiné (sans action)
     */
    public function markAsReviewed(User $admin): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }
}
