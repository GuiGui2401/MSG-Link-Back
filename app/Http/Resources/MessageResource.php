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
            'sender_id' => $this->sender_id,
            'recipient_id' => $this->recipient_id,
            'content' => $this->content,
            'sender_initial' => $this->sender_initial,
            'sender' => $this->when($this->is_identity_revealed, function () {
                return $this->sender_info;
            }),
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'is_identity_revealed' => $this->is_identity_revealed,
            'revealed_at' => $this->revealed_at?->toIso8601String(),
            'reply_to_message_id' => $this->reply_to_message_id,
            'reply_to_message' => $this->when(
                $this->relationLoaded('replyToMessage') && $this->replyToMessage,
                function () {
                    return [
                        'id' => $this->replyToMessage->id,
                        'content' => $this->replyToMessage->content,
                        'sender_initial' => $this->replyToMessage->sender_initial,
                    ];
                }
            ),
            'created_at' => $this->created_at->toIso8601String(),

            // Si l'utilisateur est l'expéditeur, montrer le destinataire
            'recipient' => $this->when(
                $request->user()?->id === $this->sender_id,
                new UserPublicResource($this->whenLoaded('recipient'))
            ),
        ];
    }
}
