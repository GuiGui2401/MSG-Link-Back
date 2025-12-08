<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'sender_initial' => $this->sender_initial,
            'sender' => $this->when($this->is_identity_revealed, function () {
                return $this->sender_info;
            }),
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'is_identity_revealed' => $this->is_identity_revealed,
            'revealed_at' => $this->revealed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            
            // Si l'utilisateur est l'expéditeur, montrer le destinataire
            'recipient' => $this->when(
                $request->user()?->id === $this->sender_id,
                new UserPublicResource($this->whenLoaded('recipient'))
            ),
        ];
    }
}
