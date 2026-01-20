<?php

namespace Database\Factories;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfessionCommentFactory extends Factory
{
    protected $model = ConfessionComment::class;

    public function definition(): array
    {
        return [
            'confession_id' => Confession::factory(),
            'author_id' => User::factory(),
            'parent_id' => null,
            'content' => $this->faker->sentence(),
            'is_anonymous' => $this->faker->boolean(20),
            'media_url' => null,
            'media_type' => null,
        ];
    }
}
