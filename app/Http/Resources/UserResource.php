<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'profile_url' => $this->profile_url,
            'is_verified' => $this->is_verified,
            'is_online' => $this->is_online,
            'role' => $this->role,
            'wallet_balance' => $this->wallet_balance,
            'formatted_balance' => $this->formatted_balance,
            'settings' => $this->settings,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),

            // Champs Premium
            'is_premium' => $this->is_premium ?? false,
            'premium_started_at' => $this->premium_started_at?->toIso8601String(),
            'premium_expires_at' => $this->premium_expires_at?->toIso8601String(),
            'premium_auto_renew' => $this->premium_auto_renew ?? false,
            'has_active_premium' => $this->has_active_premium ?? false,
            'premium_days_remaining' => $this->premium_days_remaining,

            // Champs admin uniquement
            $this->mergeWhen($request->user()?->is_admin, [
                'is_banned' => $this->is_banned,
                'banned_reason' => $this->banned_reason,
                'banned_at' => $this->banned_at?->toIso8601String(),
            ]),
        ];
    }
}
