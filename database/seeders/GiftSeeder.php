<?php

namespace Database\Seeders;

use App\Models\Gift;
use App\Models\GiftCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset gifts data before seeding
        Schema::disableForeignKeyConstraints();
        DB::table('gift_transactions')->truncate();
        DB::table('gifts')->truncate();
        DB::table('gift_categories')->truncate();
        Schema::enableForeignKeyConstraints();

        $categories = collect([
            'Romance',
            'Amitié',
            'Appréciation',
            'Célébration',
            'Premium',
        ])->mapWithKeys(function (string $name) {
            $category = GiftCategory::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );

            return [$name => $category->id];
        });

        $gifts = [
            // Bronze (1000 FCFA)
            [
                'name' => 'Cœur',
                'slug' => 'coeur',
                'description' => 'Un petit cœur pour montrer ton affection',
                'icon' => '❤️',
                'animation' => 'heart_float',
                'price' => 1000,
                'tier' => Gift::TIER_BRONZE,
                'sort_order' => 1,
                'gift_category_id' => $categories['Romance'],
            ],
            [
                'name' => 'Étoile',
                'slug' => 'etoile',
                'description' => 'Une étoile brillante',
                'icon' => '⭐',
                'animation' => 'star_sparkle',
                'price' => 1000,
                'tier' => Gift::TIER_BRONZE,
                'sort_order' => 2,
                'gift_category_id' => $categories['Amitié'],
            ],
            [
                'name' => 'Rose',
                'slug' => 'rose',
                'description' => 'Une rose romantique',
                'icon' => '🌹',
                'animation' => 'rose_bloom',
                'price' => 1000,
                'tier' => Gift::TIER_BRONZE,
                'sort_order' => 3,
                'gift_category_id' => $categories['Romance'],
            ],

            // Silver (5000 FCFA)
            [
                'name' => 'Chocolat',
                'slug' => 'chocolat',
                'description' => 'Une boîte de chocolats délicieux',
                'icon' => '🍫',
                'animation' => 'chocolate_unwrap',
                'price' => 5000,
                'tier' => Gift::TIER_SILVER,
                'sort_order' => 4,
                'gift_category_id' => $categories['Appréciation'],
            ],
            [
                'name' => 'Ours en peluche',
                'slug' => 'ours-peluche',
                'description' => 'Un adorable ours en peluche',
                'icon' => '🧸',
                'animation' => 'teddy_hug',
                'price' => 5000,
                'tier' => Gift::TIER_SILVER,
                'sort_order' => 5,
                'gift_category_id' => $categories['Amitié'],
            ],
            [
                'name' => 'Parfum',
                'slug' => 'parfum',
                'description' => 'Un parfum élégant',
                'icon' => '🧴',
                'animation' => 'perfume_spray',
                'price' => 5000,
                'tier' => Gift::TIER_SILVER,
                'sort_order' => 6,
                'gift_category_id' => $categories['Célébration'],
            ],

            // Gold (25000 FCFA)
            [
                'name' => 'Bouquet de fleurs',
                'slug' => 'bouquet',
                'description' => 'Un magnifique bouquet de fleurs',
                'icon' => '💐',
                'animation' => 'bouquet_bloom',
                'price' => 25000,
                'tier' => Gift::TIER_GOLD,
                'sort_order' => 7,
                'gift_category_id' => $categories['Célébration'],
            ],
            [
                'name' => 'Montre',
                'slug' => 'montre',
                'description' => 'Une montre de luxe',
                'icon' => '⌚',
                'animation' => 'watch_shine',
                'price' => 25000,
                'tier' => Gift::TIER_GOLD,
                'sort_order' => 8,
                'gift_category_id' => $categories['Appréciation'],
            ],
            [
                'name' => 'Champagne',
                'slug' => 'champagne',
                'description' => 'Une bouteille de champagne',
                'icon' => '🍾',
                'animation' => 'champagne_pop',
                'price' => 25000,
                'tier' => Gift::TIER_GOLD,
                'sort_order' => 9,
                'gift_category_id' => $categories['Célébration'],
            ],

            // Diamond (50000 FCFA)
            [
                'name' => 'Bague diamant',
                'slug' => 'bague-diamant',
                'description' => 'Une bague sertie de diamants',
                'icon' => '💍',
                'animation' => 'ring_sparkle',
                'price' => 50000,
                'tier' => Gift::TIER_DIAMOND,
                'sort_order' => 10,
                'gift_category_id' => $categories['Premium'],
            ],
            [
                'name' => 'Couronne',
                'slug' => 'couronne',
                'description' => 'Une couronne royale',
                'icon' => '👑',
                'animation' => 'crown_glow',
                'price' => 50000,
                'tier' => Gift::TIER_DIAMOND,
                'sort_order' => 11,
                'gift_category_id' => $categories['Premium'],
            ],
            [
                'name' => 'Yacht',
                'slug' => 'yacht',
                'description' => 'Un yacht de luxe virtuel',
                'icon' => '🛥️',
                'animation' => 'yacht_sail',
                'price' => 50000,
                'tier' => Gift::TIER_DIAMOND,
                'sort_order' => 12,
                'gift_category_id' => $categories['Premium'],
            ],
        ];

        foreach ($gifts as $gift) {
            Gift::updateOrCreate(
                ['slug' => $gift['slug']],
                $gift
            );
        }

        $this->command->info('Gifts seeded successfully!');
    }
}
