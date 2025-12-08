<?php

namespace App\Traits;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Reportable
{
    /**
     * Relation vers les signalements
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Signaler ce contenu
     */
    public function report(User $reporter, string $reason, ?string $description = null): Report
    {
        // Vérifier si l'utilisateur n'a pas déjà signalé ce contenu
        $existingReport = $this->reports()
            ->where('reporter_id', $reporter->id)
            ->where('status', Report::STATUS_PENDING)
            ->first();

        if ($existingReport) {
            return $existingReport;
        }

        return $this->reports()->create([
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'description' => $description,
            'status' => Report::STATUS_PENDING,
        ]);
    }

    /**
     * Vérifier si un utilisateur a déjà signalé ce contenu
     */
    public function isReportedBy(User $user): bool
    {
        return $this->reports()
            ->where('reporter_id', $user->id)
            ->exists();
    }

    /**
     * Obtenir le nombre de signalements en attente
     */
    public function getPendingReportsCountAttribute(): int
    {
        return $this->reports()
            ->where('status', Report::STATUS_PENDING)
            ->count();
    }

    /**
     * Vérifier si le contenu a des signalements non traités
     */
    public function getHasPendingReportsAttribute(): bool
    {
        return $this->pending_reports_count > 0;
    }
}
