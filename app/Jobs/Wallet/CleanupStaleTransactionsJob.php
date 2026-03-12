<?php

namespace App\Jobs\Wallet;

use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Job schedulé qui nettoie les transactions/withdrawals obsolètes
 * S'exécute une fois par jour via le scheduler
 */
class CleanupStaleTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes max

    public function handle()
    {
        Log::info('🧹 [CLEANUP] Starting cleanup of stale transactions...');

        $depositsUpdated = 0;
        $withdrawalsUpdated = 0;

        DB::transaction(function () use (&$depositsUpdated, &$withdrawalsUpdated) {
            // 1. Nettoyer les deposits pending depuis plus de 24 heures
            $depositsUpdated = Transaction::where('type', 'deposit')
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subHours(24))
                ->update([
                    'status' => 'failed',
                    'meta' => DB::raw("JSON_SET(COALESCE(meta, '{}'), '$.failed_reason', 'Timeout - No response after 24 hours', '$.auto_failed_at', '" . now()->toISOString() . "')"),
                ]);

            Log::info("🧹 [CLEANUP] Marked {$depositsUpdated} stale deposit(s) as failed");

            // 2. Nettoyer les withdrawals pending depuis plus de 48 heures
            $withdrawalsUpdated = Withdrawal::whereIn('status', [
                    Withdrawal::STATUS_PENDING,
                    Withdrawal::STATUS_PROCESSING,
                ])
                ->where('created_at', '<', now()->subHours(48))
                ->update([
                    'status' => Withdrawal::STATUS_FAILED,
                    'rejection_reason' => 'Timeout - No response after 48 hours',
                    'processed_at' => now(),
                ]);

            Log::info("🧹 [CLEANUP] Marked {$withdrawalsUpdated} stale withdrawal(s) as failed");
        });

        Log::info('✅ [CLEANUP] Cleanup completed', [
            'deposits_cleaned' => $depositsUpdated,
            'withdrawals_cleaned' => $withdrawalsUpdated,
            'total' => $depositsUpdated + $withdrawalsUpdated,
        ]);

        // Optionnel : Envoyer des notifications aux admins si beaucoup de transactions ont été nettoyées
        if (($depositsUpdated + $withdrawalsUpdated) > 10) {
            Log::warning('⚠️ [CLEANUP] High number of stale transactions detected', [
                'count' => $depositsUpdated + $withdrawalsUpdated,
            ]);
            // TODO: Notifier les admins
        }
    }
}
