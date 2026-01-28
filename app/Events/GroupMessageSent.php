<?php

namespace App\Events;

use App\Models\GroupMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GroupMessage $message;

    public function __construct(GroupMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.' . $this->message->group_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'group_id' => $this->message->group_id,
            'sender_id' => $this->message->sender_id,
            'sender_anonymous_name' => $this->message->sender_anonymous_name,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'reply_to_message_id' => $this->message->reply_to_message_id,
            'created_at' => $this->message->created_at->toIso8601String(),
            'is_own' => $this->message->sender_id === auth()->id(),
        ];

        if (in_array($this->message->type, [GroupMessage::TYPE_IMAGE, GroupMessage::TYPE_VOICE, GroupMessage::TYPE_VIDEO])) {
            $data['media_url'] = $this->message->media_url;
            $data['media_full_url'] = $this->message->media_full_url;
        }

        return $data;
    }
}
