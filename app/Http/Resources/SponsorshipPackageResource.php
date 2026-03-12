<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorshipPackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'reach_min' => $this->reach_min,
            'reach_max' => $this->reach_max,
            'reach_label' => $this->reach_label,
            'duration_days' => $this->duration_days,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
