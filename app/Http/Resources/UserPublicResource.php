<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser = $request->user() ?? auth('sanctum')->user();
        // Récupérer les settings de visibilité
        $settings = $this->settings ?? [];
        $showName = $settings['show_name_on_posts'] ?? true;

        return [
            'id' => $this->id,
            // Afficher le vrai nom ou cacher selon les settings
            'first_name' => $showName ? $this->first_name : 'Anonyme',
            'last_name' => $showName ? $this->last_name : null,
            'full_name' => $showName ? $this->full_name : 'Anonyme',
            'username' => $this->username,
            // La photo est toujours renvoyée même si l'utilisateur est anonyme
            'avatar_url' => $this->avatar_url,
            'bio' => $showName ? $this->bio : null,
            'profile_url' => $this->profile_url,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            // Inclure les settings de visibilité pour le frontend
            'settings' => [
                'show_name_on_posts' => $showName,
                'show_photo_on_posts' => $settings['show_photo_on_posts'] ?? true,
            ],
            'is_premium' => $this->is_premium,
            'is_verified' => $this->is_verified ?? $this->is_premium,
            'followers_count' => $this->followers_count ?? $this->followers()->count(),
            'following_count' => $this->following_count ?? $this->following()->count(),
            'confessions_count' => $this->confessions_count ?? $this->confessionsWritten()->count(),
            'is_following' => $authUser ? $authUser->isFollowing($this->resource) : false,
            'is_followed_by' => $authUser ? $this->resource->isFollowing($authUser) : false,
        ];
    }
}
