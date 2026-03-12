<?php

namespace App\Jobs\Wallet;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job schedulé qui vérifie tous les deposits pending
 * S'exécute toutes les 30 secondes via le scheduler
 */
class CheckPendingDepositsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle()
    {
        Log::info('🔍 [CHECK-DEPOSITS] Starting check for pending deposits...');

        // Trouver les deposits pending:
        // - Créés il y a au moins 30 secondes (laisser le temps à FreeMoPay de traiter)
        // - Créés il y a maximum 24 heures (après ça, le CleanupJob s'en occupe)
        $pendingDeposits = Transaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->where('created_at', '<=', now()->subSeconds(30))
            ->get();

        $count = $pendingDeposits->count();
        Log::info("📊 [CHECK-DEPOSITS] Found {$count} pending deposit(s) to verify");

        if ($count === 0) {
            return;
        }

        // Dispatcher un job pour chaque deposit
        foreach ($pendingDeposits as $deposit) {
            $meta = json_decode($deposit->meta, true) ?? [];
            $reference = $meta['provider_reference'] ?? null;

            if (!$reference) {
                Log::warning('⚠️ [CHECK-DEPOSITS] Deposit without reference', [
                    'transaction_id' => $deposit->id,
                ]);
                continue;
            }

            Log::debug('📤 [CHECK-DEPOSITS] Dispatching job for deposit', [
                'transaction_id' => $deposit->id,
                'reference' => $reference,
            ]);

            // Dispatch le job de vérification
            ProcessDepositStatusJob::dispatch($deposit->id)
                ->onQueue('deposits');
        }

        Log::info("✅ [CHECK-DEPOSITS] Dispatched {$count} verification job(s)");
    }
}
