<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'creator_id' => User::factory(),
            'invite_code' => strtoupper(Str::random(8)),
            'is_public' => $this->faker->boolean(60),
            'only_owner_can_post' => $this->faker->boolean(15),
            'max_members' => $this->faker->numberBetween(30, 150),
            'members_count' => 0,
            'messages_count' => 0,
            'last_message_at' => null,
            'avatar_url' => $this->faker->boolean(40)
                ? 'groups/avatars/' . $this->faker->uuid . '.jpg'
                : null,
        ];
    }
}
