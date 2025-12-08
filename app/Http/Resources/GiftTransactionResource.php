<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isSender = $this->sender_id === $user?->id;
        
        return [
            'id' => $this->id,
            'gift' => new GiftResource($this->whenLoaded('gift')),
            
            // Expéditeur
            'sender' => $this->when($this->relationLoaded('sender'), function () use ($isSender) {
                if ($isSender) {
                    return new UserPublicResource($this->sender);
                }
                return [
                    'id' => $this->sender->id,
                    'initial' => $this->sender->initial,
                ];
            }),
            
            // Destinataire
            'recipient' => new UserPublicResource($this->whenLoaded('recipient')),
            
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'net_amount' => $this->when(!$isSender, $this->net_amount),
            'message' => $this->message,
            'status' => $this->status,
            
            'conversation_id' => $this->conversation_id,
            
            'created_at' => $this->created_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
