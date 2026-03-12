<?php

namespace App\Jobs\Wallet;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job schedulé qui vérifie tous les withdrawals pending
 * S'exécute toutes les minutes via le scheduler
 */
class CheckPendingWithdrawalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle()
    {
        Log::info('🔍 [CHECK-WITHDRAWALS] Starting check for pending withdrawals...');

        // Trouver les withdrawals pending:
        // - Créés il y a au moins 1 minute (laisser le temps à FreeMoPay de traiter)
        // - Créés il y a maximum 48 heures
        $pendingWithdrawals = Withdrawal::whereIn('status', [
                Withdrawal::STATUS_PENDING,
                Withdrawal::STATUS_PROCESSING
            ])
            ->where('created_at', '>=', now()->subHours(48))
            ->where('created_at', '<=', now()->subMinute())
            // Éviter de vérifier trop souvent (attendre au moins 30s depuis la dernière vérification)
            ->where('updated_at', '<=', now()->subSeconds(30))
            ->get();

        $count = $pendingWithdrawals->count();
        Log::info("📊 [CHECK-WITHDRAWALS] Found {$count} pending withdrawal(s) to verify");

        if ($count === 0) {
            return;
        }

        // Dispatcher un job pour chaque withdrawal
        foreach ($pendingWithdrawals as $withdrawal) {
            // Vérifier s'il y a une référence FreeMoPay
            $reference = $withdrawal->transaction_reference;

            if (!$reference) {
                Log::warning('⚠️ [CHECK-WITHDRAWALS] Withdrawal without reference', [
                    'withdrawal_id' => $withdrawal->id,
                ]);
                continue;
            }

            Log::debug('📤 [CHECK-WITHDRAWALS] Dispatching job for withdrawal', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $reference,
            ]);

            // Dispatch le job de vérification
            ProcessWithdrawalStatusJob::dispatch($withdrawal->id)
                ->onQueue('withdrawals');
        }

        Log::info("✅ [CHECK-WITHDRAWALS] Dispatched {$count} verification job(s)");
    }
}
