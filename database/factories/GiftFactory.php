<?php

namespace Database\Factories;

use App\Models\Gift;
use App\Models\GiftCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GiftFactory extends Factory
{
    protected $model = Gift::class;

    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word());
        $tier = $this->faker->randomElement([
            Gift::TIER_BRONZE,
            Gift::TIER_SILVER,
            Gift::TIER_GOLD,
            Gift::TIER_DIAMOND,
        ]);

        $price = Gift::DEFAULT_PRICES[$tier] ?? $this->faker->numberBetween(1000, 50000);

        $categoryId = GiftCategory::query()->inRandomOrder()->value('id');

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'description' => $this->faker->optional()->sentence(),
            'icon' => $this->faker->randomElement(['🎁', '💖', '⭐', '🌟', '💐', '🍫']),
            'animation' => $this->faker->randomElement(['sparkle', 'bounce', 'glow', 'float']),
            'price' => $price,
            'tier' => $tier,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'gift_category_id' => $categoryId,
            'background_color' => $this->faker->hexColor(),
        ];
    }
}
