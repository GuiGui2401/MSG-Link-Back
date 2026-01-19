<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Déterminer si on doit afficher l'auteur
        // Auteur affiché si: post public ET pas anonyme, OU identité révélée
        $shouldShowAuthor = ($this->is_public && !$this->is_anonymous) || $this->is_identity_revealed;

        $promotion = $this->activePromotion();

        return [
            'id' => $this->id,
            'content' => $this->content,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'video' => $this->video,
            'video_url' => $this->video_url,
            'type' => $this->type,
            'is_public' => $this->is_public,
            'is_private' => $this->is_private,
            'is_anonymous' => $this->is_anonymous ?? false,
            'status' => $this->status,
            'is_approved' => $this->is_approved,
            'is_pending' => $this->is_pending,

            // Auteur - affiché si non anonyme ou identité révélée
            'author_initial' => $this->author_initial,
            'author' => $shouldShowAuthor && $this->author ? [
                'id' => $this->author->id,
                'username' => $this->author->username,
                'first_name' => $this->author->first_name,
                'full_name' => $this->author->full_name,
                'avatar_url' => $this->author->avatar_url,
                'is_premium' => $this->author->is_premium,
                'is_verified' => $this->author->is_verified ?? false,
            ] : null,
            'is_identity_revealed' => $this->is_identity_revealed,
            'is_sponsored' => $this->isPromoted(),
            'promotion_id' => optional($this->activePromotion())->id,
            'promotion' => $promotion ? [
                'id' => $promotion->id,
                'goal' => $promotion->goal,
                'cta_label' => $promotion->cta_label,
                'website_url' => $promotion->website_url,
            ] : null,

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
