<?php

namespace App\Events;

use App\Models\AnonymousMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AnonymousMessage $message;

    public function __construct(AnonymousMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_initial' => $this->message->sender_initial,
            'content_preview' => \Illuminate\Support\Str::limit($this->message->content, 50),
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
