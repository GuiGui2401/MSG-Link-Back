<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PremiumSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === 'conversation' ? 'Conversation' : 'Message',
            
            // Utilisateur cible
            'target_user' => new UserPublicResource($this->whenLoaded('targetUser')),
            
            // Références
            'conversation_id' => $this->conversation_id,
            'message_id' => $this->message_id,
            
            'amount' => $this->amount,
            'formatted_amount' => number_format($this->amount, 0, ',', ' ') . ' FCFA',
            
            'status' => $this->status,
            'is_active' => $this->is_active,
            'auto_renew' => $this->auto_renew,
            
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->is_active ? $this->expires_at?->diffInDays(now()) : 0,
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
