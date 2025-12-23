<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LegalPage;

class LegalPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'cgu',
                'title' => 'Conditions d\'utilisation',
                'content' => null,
                'is_active' => false,
                'order' => 1,
            ],
            [
                'slug' => 'privacy',
                'title' => 'Politique de confidentialité',
                'content' => null,
                'is_active' => false,
                'order' => 2,
            ],
            [
                'slug' => 'cookies',
                'title' => 'Politique des cookies',
                'content' => null,
                'is_active' => false,
                'order' => 3,
            ],
            [
                'slug' => 'community-rules',
                'title' => 'Règles de la communauté',
                'content' => null,
                'is_active' => false,
                'order' => 4,
            ],
            [
                'slug' => 'legal-notice',
                'title' => 'Mentions légales',
                'content' => null,
                'is_active' => false,
                'order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }

        $this->command->info('Pages légales créées avec succès !');
    }
}
