<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupMessageFactory extends Factory
{
    protected $model = GroupMessage::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            GroupMessage::TYPE_TEXT,
            GroupMessage::TYPE_IMAGE,
            GroupMessage::TYPE_VOICE,
            GroupMessage::TYPE_VIDEO,
        ]);

        return [
            'group_id' => Group::factory(),
            'sender_id' => User::factory(),
            'content' => $type === GroupMessage::TYPE_TEXT ? $this->faker->sentence() : null,
            'type' => $type,
            'media_url' => match ($type) {
                GroupMessage::TYPE_IMAGE => 'group_messages/images/' . $this->faker->uuid . '.jpg',
                GroupMessage::TYPE_VOICE => 'group_messages/voices/' . $this->faker->uuid . '.wav',
                GroupMessage::TYPE_VIDEO => 'group_messages/videos/' . $this->faker->uuid . '.mp4',
                default => null,
            },
            'voice_effect' => $type === GroupMessage::TYPE_VOICE
                ? $this->faker->randomElement(['robot', 'echo', 'deep', 'chipmunk'])
                : null,
            'reply_to_message_id' => null,
        ];
    }
}
