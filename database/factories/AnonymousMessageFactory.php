<?php

namespace Database\Factories;

use App\Models\AnonymousMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnonymousMessageFactory extends Factory
{
    protected $model = AnonymousMessage::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_read' => false,
            'read_at' => null,
            'is_identity_revealed' => false,
            'revealed_at' => null,
            'revealed_via_subscription_id' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function revealed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_identity_revealed' => true,
            'revealed_at' => now(),
        ]);
    }
}
