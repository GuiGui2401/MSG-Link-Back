<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'type' => $this->type,
            'is_public' => $this->is_public,
            'is_private' => $this->is_private,
            'status' => $this->status,
            'is_approved' => $this->is_approved,
            'is_pending' => $this->is_pending,
            
            // Auteur (masqué sauf si révélé)
            'author_initial' => $this->author_initial,
            'author' => $this->when($this->is_identity_revealed, function () {
                return $this->author_info;
            }),
            'is_identity_revealed' => $this->is_identity_revealed,
            
            // Destinataire (pour confessions privées)
            'recipient' => $this->when(
                $this->is_private,
                new UserPublicResource($this->whenLoaded('recipient'))
            ),
            
            // Stats pour confessions publiques
            'likes_count' => $this->when($this->is_public, $this->liked_by_count ?? $this->likes_count ?? 0),
            'views_count' => $this->when($this->is_public, $this->views_count ?? 0),
            'comments_count' => $this->when($this->is_public, $this->comments_count ?? 0),
            'is_liked' => $this->when(isset($this->is_liked), $this->is_liked),
            
            // Modération (admin seulement)
            $this->mergeWhen($request->user()?->is_admin || $request->user()?->is_moderator, [
                'moderated_by' => $this->whenLoaded('moderator', fn() => $this->moderator->username),
                'moderated_at' => $this->moderated_at?->toIso8601String(),
                'rejection_reason' => $this->rejection_reason,
            ]),
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
