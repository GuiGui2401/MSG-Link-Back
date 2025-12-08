<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isMine = $this->sender_id === $user?->id;
        
        // Vérifier si l'utilisateur a un premium pour cette conversation
        $hasPremium = $this->conversation?->hasPremiumSubscription($user);
        
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'content' => $this->content,
            'type' => $this->type,
            'is_mine' => $isMine,
            
            // Expéditeur
            'sender' => [
                'id' => $this->sender->id,
                'initial' => $this->sender->initial,
                'first_name' => ($isMine || $hasPremium) ? $this->sender->first_name : null,
                'avatar_url' => ($isMine || $hasPremium) ? $this->sender->avatar_url : null,
            ],
            
            // Si c'est un message cadeau
            'gift' => $this->when($this->type === 'gift' && $this->relationLoaded('giftTransaction'), function () {
                return [
                    'id' => $this->giftTransaction?->gift?->id,
                    'name' => $this->giftTransaction?->gift?->name,
                    'icon' => $this->giftTransaction?->gift?->icon,
                    'animation' => $this->giftTransaction?->gift?->animation,
                    'tier' => $this->giftTransaction?->gift?->tier,
                    'amount' => $this->giftTransaction?->amount,
                ];
            }),
            
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
