<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=30 : Nombre de jours}';
    
    protected $description = 'Supprimer les notifications lues anciennes';

    public function handle(): int
    {
        $days = $this->option('days');
        
        $deleted = Notification::whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Supprimé {$deleted} notifications lues de plus de {$days} jours.");

        return self::SUCCESS;
    }
}
