<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredStories extends Command
{
    protected $signature = 'stories:cleanup {--hours=24 : Nombre d\'heures après expiration}';

    protected $description = 'Supprimer les stories expirées et leurs médias associés';

    public function handle(): int
    {
        $hours = $this->option('hours');

        // Étape 1: Marquer toutes les stories expirées
        $this->info('🔍 Marquage des stories expirées...');
        $markedCount = Story::markExpiredStories();
        $this->info("✓ Marqué {$markedCount} stories comme expirées");

        // Étape 2: Supprimer les stories expirées depuis plus de X heures
        $this->info("🗑️  Suppression des stories expirées depuis plus de {$hours}h...");

        $expiredStories = Story::withTrashed()
            ->where('expires_at', '<=', now()->subHours($hours))
            ->get();

        $deletedCount = 0;
        $deletedMediaCount = 0;

        foreach ($expiredStories as $story) {
            // Supprimer le média du stockage
            if ($story->media_url && Storage::disk('public')->exists($story->media_url)) {
                Storage::disk('public')->delete($story->media_url);
                $deletedMediaCount++;
            }

            // Supprimer le thumbnail du stockage
            if ($story->thumbnail_url && Storage::disk('public')->exists($story->thumbnail_url)) {
                Storage::disk('public')->delete($story->thumbnail_url);
                $deletedMediaCount++;
            }

            // Supprimer la story de la base de données (force delete)
            $story->forceDelete();
            $deletedCount++;
        }

        $this->info("✅ Supprimé {$deletedCount} stories expirées depuis plus de {$hours}h");
        $this->info("📁 Supprimé {$deletedMediaCount} fichiers médias associés");

        return self::SUCCESS;
    }
}
