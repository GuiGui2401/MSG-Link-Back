<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLog extends Model
{
    use HasFactory;

    /**
     * Pas de updated_at pour les logs
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Actions communes
     */
    const ACTION_BAN_USER = 'ban_user';
    const ACTION_UNBAN_USER = 'unban_user';
    const ACTION_DELETE_USER = 'delete_user';
    const ACTION_APPROVE_CONFESSION = 'approve_confession';
    const ACTION_REJECT_CONFESSION = 'reject_confession';
    const ACTION_RESOLVE_REPORT = 'resolve_report';
    const ACTION_DISMISS_REPORT = 'dismiss_report';
    const ACTION_PROCESS_WITHDRAWAL = 'process_withdrawal';
    const ACTION_REJECT_WITHDRAWAL = 'reject_withdrawal';
    const ACTION_DELETE_CONTENT = 'delete_content';
    const ACTION_UPDATE_SETTINGS = 'update_settings';

    // ==================== RELATIONS ====================

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // ==================== ACCESSORS ====================

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_BAN_USER => 'Bannissement utilisateur',
            self::ACTION_UNBAN_USER => 'Débannissement utilisateur',
            self::ACTION_DELETE_USER => 'Suppression utilisateur',
            self::ACTION_APPROVE_CONFESSION => 'Approbation confession',
            self::ACTION_REJECT_CONFESSION => 'Rejet confession',
            self::ACTION_RESOLVE_REPORT => 'Résolution signalement',
            self::ACTION_DISMISS_REPORT => 'Rejet signalement',
            self::ACTION_PROCESS_WITHDRAWAL => 'Traitement retrait',
            self::ACTION_REJECT_WITHDRAWAL => 'Rejet retrait',
            self::ACTION_DELETE_CONTENT => 'Suppression contenu',
            self::ACTION_UPDATE_SETTINGS => 'Mise à jour paramètres',
            default => $this->action,
        };
    }

    public function getModelTypeLabelAttribute(): ?string
    {
        if (!$this->model_type) {
            return null;
        }

        return match ($this->model_type) {
            User::class => 'Utilisateur',
            Confession::class => 'Confession',
            Report::class => 'Signalement',
            Withdrawal::class => 'Retrait',
            AnonymousMessage::class => 'Message',
            default => class_basename($this->model_type),
        };
    }

    // ==================== SCOPES ====================

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== METHODS ====================

    /**
     * Créer un log admin
     */
    public static function log(
        User $admin,
        string $action,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = []
    ): self {
        return self::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
