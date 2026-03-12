<?php

namespace Database\Seeders;

use App\Models\Sponsorship;
use App\Models\SponsorshipPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SponsorshipFakerSeeder extends Seeder
{
    /**
     * Seed a lot of sponsorships for feed UI testing.
     *
     * Run:
     *   php artisan db:seed --class=SponsorshipFakerSeeder
     */
    public function run(): void
    {
        $count = (int) (env('SPONSORSHIP_FAKE_COUNT', 250));
        $count = max(1, min($count, 2000));

        $this->command?->info("Seeding {$count} sponsorships...");

        $packages = SponsorshipPackage::query()->active()->get();
        if ($packages->isEmpty()) {
            $this->command?->warn('No active sponsorship packages found. Creating defaults...');
            $packages = collect([
                SponsorshipPackage::create([
                    'name' => 'BOOST START',
                    'description' => 'Package test (seed)',
                    'reach_min' => 100,
                    'reach_max' => 500,
                    'price' => 1000,
                    'duration_days' => 3,
                    'is_active' => true,
                ]),
                SponsorshipPackage::create([
                    'name' => 'BOOST PLUS',
                    'description' => 'Package test (seed)',
                    'reach_min' => 500,
                    'reach_max' => 2000,
                    'price' => 5000,
                    'duration_days' => 5,
                    'is_active' => true,
                ]),
                SponsorshipPackage::create([
                    'name' => 'BOOST PRO',
                    'description' => 'Package test (seed)',
                    'reach_min' => 2000,
                    'reach_max' => 10000,
                    'price' => 20000,
                    'duration_days' => 7,
                    'is_active' => true,
                ]),
            ]);
        }

        $users = User::query()
            ->where('role', 'user')
            ->where('is_banned', false)
            ->inRandomOrder()
            ->limit(80)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->error('No users found. Run FakeDataSeeder first.');
            return;
        }

        // Reuse existing media stored in the public disk.
        $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $videoExt = ['mp4', 'mov', 'webm', 'avi'];

        $candidateDirs = [
            'sponsoring',
            'confessions',
            'stories',
            'covers',
            'anonymous_messages/images',
            'comments/images',
            'chat/images',
            'avatars',
        ];

        $allFiles = collect();
        foreach ($candidateDirs as $dir) {
            try {
                $allFiles = $allFiles->merge(Storage::disk('public')->allFiles($dir));
            } catch (\Throwable $e) {
                // Ignore missing dirs.
            }
        }

        $images = $allFiles
            ->filter(fn ($p) => in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), $imageExt, true))
            ->values();
        $videos = $allFiles
            ->filter(fn ($p) => in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), $videoExt, true))
            ->values();

        if ($images->isEmpty()) {
            $this->command?->warn('No images found in storage/app/public. Image sponsorships will fallback to text.');
        }
        if ($videos->isEmpty()) {
            $this->command?->warn('No videos found in storage/app/public. Video sponsorships will fallback to text.');
        }

        $texts = [
            'Offre du jour: -30% sur nos services. Contactez-nous.',
            'Besoin d’un site/app? On vous accompagne de A à Z.',
            'Cours particuliers disponibles. Inscriptions ouvertes.',
            'Livraison rapide dans toute la ville. Essayez maintenant.',
            'Nouveau produit en stock. Quantites limitees.',
            'Promo: 1 achete, 1 offert. Valable cette semaine.',
            'Service client disponible 7j/7. Ecrivez-nous.',
            'On recrute. Envoyez votre CV en message.',
        ];

        $now = Carbon::now();
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $package = $packages->random();
            $user = $users->random();

            // Weighted distribution: image 45%, text 40%, video 15%
            $roll = random_int(1, 100);
            $mediaType = Sponsorship::MEDIA_TEXT;
            if ($roll <= 45) {
                $mediaType = Sponsorship::MEDIA_IMAGE;
            } elseif ($roll <= 60) {
                $mediaType = Sponsorship::MEDIA_VIDEO;
            }

            $text = null;
            $mediaUrl = null;

            if ($mediaType === Sponsorship::MEDIA_TEXT) {
                $text = $texts[array_rand($texts)] . ' #' . Str::upper(Str::random(4));
            } elseif ($mediaType === Sponsorship::MEDIA_IMAGE) {
                if ($images->isNotEmpty()) {
                    $mediaUrl = $images->random();
                } else {
                    $mediaType = Sponsorship::MEDIA_TEXT;
                    $text = $texts[array_rand($texts)] . ' #' . Str::upper(Str::random(4));
                }
            } else { // video
                if ($videos->isNotEmpty()) {
                    $mediaUrl = $videos->random();
                } else {
                    $mediaType = Sponsorship::MEDIA_TEXT;
                    $text = $texts[array_rand($texts)] . ' #' . Str::upper(Str::random(4));
                }
            }

            $target = (int) ($package->reach_max ?: $package->reach_min);
            $delivered = $target > 0 ? random_int(0, (int) max(1, floor($target * 0.08))) : 0;

            $createdAt = $now->copy()->subMinutes(random_int(5, 60 * 72)); // last 3 days
            $endsAt = $now->copy()->addDays((int) $package->duration_days)->addHours(random_int(0, 18));

            Sponsorship::create([
                'user_id' => $user->id,
                'sponsorship_package_id' => $package->id,
                'media_type' => $mediaType,
                'text_content' => $mediaType === Sponsorship::MEDIA_TEXT ? $text : null,
                'media_url' => $mediaType !== Sponsorship::MEDIA_TEXT ? $mediaUrl : null,
                'price' => (int) $package->price,
                'reach_min' => (int) $package->reach_min,
                'reach_max' => (int) $package->reach_max,
                'duration_days' => (int) $package->duration_days,
                'ends_at' => $endsAt,
                'status' => Sponsorship::STATUS_ACTIVE,
                'delivered_count' => $delivered,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $created++;
            if ($this->command && $created % 50 === 0) {
                $this->command->info("... {$created}/{$count}");
            }
        }

        $this->command?->info("Done. Created {$created} sponsorships.");
    }
}

