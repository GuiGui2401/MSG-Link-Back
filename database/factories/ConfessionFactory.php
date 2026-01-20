<?php

namespace Database\Factories;

use App\Models\Confession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfessionFactory extends Factory
{
    protected $model = Confession::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            Confession::TYPE_PUBLIC,
            Confession::TYPE_PRIVATE,
        ]);

        $isRevealed = $this->faker->boolean(10);
        $revealedAt = $isRevealed ? $this->faker->dateTimeBetween('-10 days', 'now') : null;

        return [
            'author_id' => User::factory(),
            'recipient_id' => $type === Confession::TYPE_PRIVATE ? User::factory() : null,
            'content' => $this->faker->paragraph(),
            'image' => $this->faker->boolean(20)
                ? 'confessions/images/' . $this->faker->uuid . '.jpg'
                : null,
            'video' => $this->faker->boolean(15)
                ? 'confessions/videos/' . $this->faker->uuid . '.mp4'
                : null,
            'type' => $type,
            'status' => Confession::STATUS_APPROVED,
            'moderated_by' => null,
            'moderated_at' => null,
            'rejection_reason' => null,
            'is_identity_revealed' => $isRevealed,
            'revealed_at' => $revealedAt,
            'likes_count' => $this->faker->numberBetween(0, 200),
            'views_count' => $this->faker->numberBetween(0, 1500),
            'is_anonymous' => $this->faker->boolean(25),
        ];
    }
}
