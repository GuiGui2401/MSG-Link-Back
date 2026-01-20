<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $streakCount = $this->faker->numberBetween(0, 120);
        $streakUpdatedAt = $streakCount > 0
            ? Carbon::now()->subHours($this->faker->numberBetween(1, 20))
            : null;

        return [
            'participant_one_id' => User::factory(),
            'participant_two_id' => User::factory(),
            'last_message_at' => Carbon::now()->subMinutes($this->faker->numberBetween(0, 2000)),
            'streak_count' => $streakCount,
            'streak_updated_at' => $streakUpdatedAt,
            'flame_level' => $this->calculateFlameLevel($streakCount),
            'message_count' => $this->faker->numberBetween(1, 120),
        ];
    }

    private function calculateFlameLevel(int $streakCount): string
    {
        if ($streakCount >= 30) {
            return Conversation::FLAME_PURPLE;
        }

        if ($streakCount >= 7) {
            return Conversation::FLAME_ORANGE;
        }

        if ($streakCount >= 2) {
            return Conversation::FLAME_YELLOW;
        }

        return Conversation::FLAME_NONE;
    }
}
