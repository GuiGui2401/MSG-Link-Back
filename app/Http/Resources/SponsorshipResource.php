<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'owner' => $this->whenLoaded('user', fn() => new UserPublicResource($this->user)),
            'package_id' => $this->sponsorship_package_id,
            'media_type' => $this->media_type,
            'text_content' => $this->text_content,
            'media_url' => $this->when($this->media_url, fn() => url('storage/' . $this->media_url)),
            'price' => $this->price,
            'reach_min' => $this->reach_min,
            'reach_max' => $this->reach_max,
            'duration_days' => $this->duration_days,
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status,
            'delivered_count' => $this->delivered_count,
            'target_reach' => $this->target_reach,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
