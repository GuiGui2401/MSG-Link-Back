<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('6########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'avatar' => null,
            'bio' => fake()->optional()->sentence(),
            'is_banned' => false,
            'banned_reason' => null,
            'banned_at' => null,
            'wallet_balance' => 0,
            'last_seen_at' => now(),
            'settings' => [],
            'role' => 'user',
            'fcm_token' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_banned' => true,
            'banned_reason' => 'Violation des conditions d\'utilisation',
            'banned_at' => now(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
        ]);
    }

    public function withBalance(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'wallet_balance' => $amount,
        ]);
    }
}
