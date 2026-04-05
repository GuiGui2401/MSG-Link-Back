<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'cover_photo' => $this->cover_photo,
            'cover_photo_url' => $this->cover_photo_url,
            'bio' => $this->bio,
            'profile_url' => $this->profile_url,
            'is_verified' => $this->is_verified,
            'is_premium' => $this->is_premium,
            'has_active_premium' => $this->has_active_premium,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
