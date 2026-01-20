<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            GiftSeeder::class,
            DemoDataSeeder::class,
        ]);
    }

    /**
     * Seed database with fake data for testing.
     * Run with: php artisan db:seed --class=FakeDataSeeder
     */
    public function runWithFakeData(): void
    {
        $this->call([
            GiftSeeder::class,
            FakeDataSeeder::class,
            LegalPagesContentSeeder::class,
            SettingsSeeder::class,
            LegalPagesSeeder::class,
        ]);
    }
}
