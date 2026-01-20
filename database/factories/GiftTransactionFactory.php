<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GiftTransactionFactory extends Factory
{
    protected $model = GiftTransaction::class;

    public function definition(): array
    {
        $gift = Gift::query()->inRandomOrder()->first() ?? Gift::factory()->create();
        $amounts = GiftTransaction::calculateAmounts($gift->price);

        return [
            'gift_id' => $gift->id,
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'conversation_id' => Conversation::factory(),
            'amount' => $amounts['amount'],
            'platform_fee' => $amounts['platform_fee'],
            'net_amount' => $amounts['net_amount'],
            'status' => GiftTransaction::STATUS_COMPLETED,
            'payment_reference' => 'gift_' . Str::uuid(),
            'message' => $this->faker->optional()->sentence(),
            'is_anonymous' => $this->faker->boolean(15),
        ];
    }
}
