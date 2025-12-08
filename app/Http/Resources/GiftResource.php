<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'animation' => $this->animation,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'tier' => $this->tier,
            'tier_color' => $this->tier_color,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
