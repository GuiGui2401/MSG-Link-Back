<?php

namespace App\Events;

use App\Models\GiftTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GiftSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GiftTransaction $transaction;

    public function __construct(GiftTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('user.' . $this->transaction->recipient_id),
        ];

        // Aussi diffuser dans la conversation si présente
        if ($this->transaction->conversation_id) {
            $channels[] = new PrivateChannel('conversation.' . $this->transaction->conversation_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'gift.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->transaction->id,
            'gift' => [
                'id' => $this->transaction->gift->id,
                'name' => $this->transaction->gift->name,
                'icon' => $this->transaction->gift->icon,
                'animation' => $this->transaction->gift->animation,
                'tier' => $this->transaction->gift->tier,
            ],
            'sender_initial' => $this->transaction->sender->initial,
            'amount' => $this->transaction->net_amount,
            'message' => $this->transaction->message,
            'conversation_id' => $this->transaction->conversation_id,
            'created_at' => $this->transaction->created_at->toIso8601String(),
        ];
    }
}
