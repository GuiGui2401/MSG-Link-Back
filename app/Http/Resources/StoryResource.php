<?php

namespace App\Http\Resources;

use App\Models\PremiumSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user && $user->id === $this->user_id;
        $hasSubscription = $user && PremiumSubscription::hasActiveForStory($user->id, $this->id);
        $shouldRevealIdentity = $isOwner || $hasSubscription;

        return [
            'id' => $this->id,
            'user' => [
                'id' => $shouldRevealIdentity ? $this->user->id : null,
                'username' => $shouldRevealIdentity ? $this->user->username : 'Anonyme',
                'full_name' => $shouldRevealIdentity ? $this->user->full_name : 'Utilisateur Anonyme',
                'avatar_url' => $shouldRevealIdentity ? $this->user->avatar_url : 'https://ui-avatars.com/api/?name=Anonyme&background=667eea&color=fff',
            ],
            'is_anonymous' => !$shouldRevealIdentity,
            'can_reveal' => !$isOwner && !$hasSubscription,
            'type' => $this->type,
            'media_url' => $this->media_full_url,
            'content' => $this->content,
            'thumbnail_url' => $this->thumbnail_full_url,
            'background_color' => $this->background_color,
            'duration' => $this->duration,
            'views_count' => $this->views_count,
            'status' => $this->status,
            'is_expired' => $this->is_expired,
            'is_active' => $this->is_active,
            'time_remaining' => $this->time_remaining,
            'expires_at' => $this->expires_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            // Informations supplémentaires si demandées
            'is_viewed' => $this->when(isset($this->is_viewed), $this->is_viewed),
            'viewers' => $this->when($isOwner, function () use ($user) {
                $hasViewerSubscription = PremiumSubscription::hasActiveForStoryViewers($user->id, $this->id);

                if ($hasViewerSubscription) {
                    // Si l'utilisateur a payé, montrer la liste complète
                    return $this->getViewers()->map(fn($viewer) => [
                        'id' => $viewer->id,
                        'username' => $viewer->username,
                        'full_name' => $viewer->full_name,
                        'avatar_url' => $viewer->avatar_url,
                        'viewed_at' => $viewer->pivot->created_at->toIso8601String(),
                    ]);
                } else {
                    // Sinon, retourner juste le count
                    return [
                        'count' => $this->views_count,
                        'details_locked' => true,
                    ];
                }
            }),
            'viewers_count' => $this->when($isOwner, $this->views_count),
            'has_viewer_subscription' => $this->when($isOwner, function () use ($user) {
                return PremiumSubscription::hasActiveForStoryViewers($user->id, $this->id);
            }),
        ];
    }
}
