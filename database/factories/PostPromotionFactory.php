<?php

namespace Database\Factories;

use App\Models\Confession;
use App\Models\PostPromotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostPromotionFactory extends Factory
{
    protected $model = PostPromotion::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-5 days', '+2 days');
        $durationDays = $this->faker->numberBetween(1, 7);
        $endsAt = (clone $startsAt)->modify("+{$durationDays} days");

        return [
            'confession_id' => Confession::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'duration_hours' => $durationDays * 24,
            'reach_boost' => $this->faker->numberBetween(50, 300),
            'goal' => $this->faker->randomElement(['views', 'profile', 'followers', 'messages', 'website']),
            'sub_goal' => null,
            'audience_mode' => $this->faker->randomElement(['auto', 'custom']),
            'gender' => $this->faker->randomElement(['all', 'male', 'female']),
            'age_range' => $this->faker->randomElement(['18-24', '25-34', '35-44']),
            'locations' => [$this->faker->country()],
            'interests' => [$this->faker->word(), $this->faker->word()],
            'language' => $this->faker->randomElement(['fr', 'en']),
            'device_type' => $this->faker->randomElement(['android', 'ios']),
            'budget_mode' => $this->faker->randomElement(['daily', 'total']),
            'daily_budget' => $this->faker->randomFloat(2, 1000, 10000),
            'total_budget' => $this->faker->randomFloat(2, 5000, 50000),
            'duration_days' => $durationDays,
            'cta_label' => $this->faker->randomElement(['Voir plus', 'Suivre', 'Visiter le site']),
            'website_url' => $this->faker->optional()->url(),
            'branded_content' => $this->faker->boolean(20),
            'payment_method' => $this->faker->randomElement(['wallet', 'card']),
            'estimated_views' => $this->faker->randomFloat(2, 1000, 20000),
            'estimated_reach' => $this->faker->randomFloat(2, 800, 15000),
            'estimated_cpv' => $this->faker->randomFloat(2, 5, 50),
            'campaign_id' => (string) Str::uuid(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $this->faker->randomElement([
                PostPromotion::STATUS_ACTIVE,
                PostPromotion::STATUS_EXPIRED,
            ]),
            'impressions' => $this->faker->numberBetween(0, 5000),
            'clicks' => $this->faker->numberBetween(0, 500),
        ];
    }
}
