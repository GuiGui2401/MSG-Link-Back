<?php

namespace App\Jobs;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Story;
use App\Models\StoryReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStoryReplyChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private int $senderId,
        private int $storyOwnerId,
        private int $storyReplyId,
        private string $messageType,
        private ?string $content,
        private ?string $mediaUrl,
        private ?string $voiceEffect,
    ) {}

    public function handle(): void
    {
        $sender = User::find($this->senderId);
        $storyOwner = User::find($this->storyOwnerId);

        if (!$sender || !$storyOwner) {
            Log::warning('ProcessStoryReplyChat: user not found', [
                'sender_id' => $this->senderId,
                'story_owner_id' => $this->storyOwnerId,
            ]);
            return;
        }

        $conversation = $sender->getOrCreateConversationWith($storyOwner);

        $chatData = [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'content' => $this->content ?? '',
            'type' => $this->messageType,
            'story_reply_id' => $this->storyReplyId,
        ];

        if ($this->messageType === ChatMessage::TYPE_VOICE && $this->mediaUrl) {
            $chatData['voice_url'] = $this->mediaUrl;
            $chatData['voice_effect'] = $this->voiceEffect;
        } elseif ($this->messageType === ChatMessage::TYPE_IMAGE && $this->mediaUrl) {
            $chatData['image_url'] = $this->mediaUrl;
        }

        $chatMessage = ChatMessage::create($chatData);
        $conversation->updateAfterMessage();

        $chatMessage->load(['conversation', 'sender:id,first_name,last_name,username,avatar']);

        try {
            broadcast(new ChatMessageSent($chatMessage))->toOthers();
        } catch (\Exception $e) {
            Log::error('ProcessStoryReplyChat broadcast failed: ' . $e->getMessage());
        }
    }
}
