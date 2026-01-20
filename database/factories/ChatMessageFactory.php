<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            ChatMessage::TYPE_TEXT,
            ChatMessage::TYPE_IMAGE,
            ChatMessage::TYPE_VOICE,
            ChatMessage::TYPE_VIDEO,
        ]);

        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'content' => $type === ChatMessage::TYPE_TEXT ? $this->faker->sentence() : null,
            'type' => $type,
            'image_url' => $type === ChatMessage::TYPE_IMAGE
                ? 'chat_messages/images/' . $this->faker->uuid . '.jpg'
                : null,
            'voice_url' => $type === ChatMessage::TYPE_VOICE
                ? 'chat_messages/voices/' . $this->faker->uuid . '.wav'
                : null,
            'voice_effect' => $type === ChatMessage::TYPE_VOICE
                ? $this->faker->randomElement(['robot', 'echo', 'deep', 'chipmunk'])
                : null,
            'video_url' => $type === ChatMessage::TYPE_VIDEO
                ? 'chat_messages/videos/' . $this->faker->uuid . '.mp4'
                : null,
            'gift_transaction_id' => null,
            'anonymous_message_id' => null,
            'reply_to_message_id' => null,
            'is_read' => $this->faker->boolean(60),
            'read_at' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-5 days', 'now') : null,
        ];
    }
}
