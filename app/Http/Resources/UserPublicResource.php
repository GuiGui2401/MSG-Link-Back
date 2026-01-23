<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser = $request->user() ?? auth('sanctum')->user();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            'cover_url' => $this->cover_image_url,
            'bio' => $this->bio,
            'profile_url' => $this->profile_url,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
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
