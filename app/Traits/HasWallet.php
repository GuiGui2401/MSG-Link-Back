<?php

namespace App\Traits;

use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HasWallet
{
    /**
     * Créditer le wallet
     */
    public function creditWallet(int $amount, string $description, ?Model $source = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $source) {
            $balanceBefore = $this->wallet_balance;
            $balanceAfter = $balanceBefore + $amount;

            // Mettre à jour le solde
            $this->update(['wallet_balance' => $balanceAfter]);

            // Créer la transaction
            return WalletTransaction::create([
                'user_id' => $this->id,
                'type' => WalletTransaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id' => $source?->id,
            ]);
        });
    }

    /**
     * Débiter le wallet
     */
    public function debitWallet(int $amount, string $description, ?Model $source = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $source) {
            $balanceBefore = $this->wallet_balance;
            $balanceAfter = $balanceBefore - $amount;

            if ($balanceAfter < 0) {
                throw new \Exception('Solde insuffisant');
            }

            // Mettre à jour le solde
            $this->update(['wallet_balance' => $balanceAfter]);

            // Créer la transaction
            return WalletTransaction::create([
                'user_id' => $this->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id' => $source?->id,
            ]);
        });
    }

    /**
     * Vérifier si le solde est suffisant
     */
    public function hasEnoughBalance(int $amount): bool
    {
        return $this->wallet_balance >= $amount;
    }

    /**
     * Obtenir le solde formaté
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->wallet_balance, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Obtenir les revenus totaux (cadeaux reçus)
     */
    public function getTotalEarningsAttribute(): int
    {
        return $this->walletTransactions()
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->sum('amount');
    }

    /**
     * Obtenir les retraits totaux
     */
    public function getTotalWithdrawalsAttribute(): int
    {
        return $this->walletTransactions()
            ->where('type', WalletTransaction::TYPE_DEBIT)
            ->sum('amount');
    }
}
