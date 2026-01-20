<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        $amount = $this->faker->numberBetween(500, 50000);
        $isCredit = $this->faker->boolean(60);
        $balanceBefore = $this->faker->numberBetween(0, 200000);
        $balanceAfter = $isCredit ? $balanceBefore + $amount : max(0, $balanceBefore - $amount);

        return [
            'user_id' => User::factory(),
            'type' => $isCredit ? WalletTransaction::TYPE_CREDIT : WalletTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $isCredit ? 'Crédit portefeuille' : 'Débit portefeuille',
            'reference' => 'wallet_' . Str::uuid(),
            'transactionable_type' => null,
            'transactionable_id' => null,
        ];
    }
}
