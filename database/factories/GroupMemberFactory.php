<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupMemberFactory extends Factory
{
    protected $model = GroupMember::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement([
                GroupMember::ROLE_ADMIN,
                GroupMember::ROLE_MODERATOR,
                GroupMember::ROLE_MEMBER,
            ]),
            'joined_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'last_read_at' => $this->faker->boolean(60)
                ? $this->faker->dateTimeBetween('-5 days', 'now')
                : null,
            'is_muted' => $this->faker->boolean(10),
        ];
    }
}
