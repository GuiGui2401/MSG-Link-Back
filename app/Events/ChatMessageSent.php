<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;

        \Log::info('🔊 [EVENT] ChatMessageSent créé', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'type' => $message->type,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $data = [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_initial' => $this->message->sender->initial,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'created_at' => $this->message->created_at->toIso8601String(),
            'is_mine' => $this->message->sender_id === auth()->id(),
        ];

        if (in_array($this->message->type, [ChatMessage::TYPE_IMAGE, ChatMessage::TYPE_VOICE, ChatMessage::TYPE_VIDEO])) {
            $data['media_url'] = $this->message->media_url;
            $data['media_full_url'] = $this->message->media_full_url;
        }

        // Ajouter les données du cadeau si c'est un message de type gift
        if ($this->message->type === ChatMessage::TYPE_GIFT && $this->message->giftTransaction) {
            $gift = $this->message->giftTransaction->gift;
            $data['gift_data'] = [
                'id' => $gift->id,
                'name' => $gift->name,
                'icon' => $gift->icon,
                'price' => $gift->price,
                'formatted_price' => $gift->formatted_price,
                'tier' => $gift->tier,
                'background_color' => $gift->background_color,
                'description' => $gift->description,
                'is_anonymous' => $this->message->giftTransaction->is_anonymous,
            ];
        }

        // Ajouter le message anonyme cité si présent (réponse à un message anonyme)
        if ($this->message->anonymousMessage) {
            $data['anonymous_message'] = [
                'id' => $this->message->anonymousMessage->id,
                'content' => $this->message->anonymousMessage->content,
                'created_at' => $this->message->anonymousMessage->created_at->toIso8601String(),
            ];
        }

        return $data;
    }
}
