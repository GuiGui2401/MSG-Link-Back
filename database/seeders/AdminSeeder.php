<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin principal
        User::updateOrCreate(
            ['email' => 'admin@msglink.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'superadmin',
                'email' => 'admin@msglink.com',
                'phone' => '237600000000',
                'password' => Hash::make('Admin@123!'),
                'role' => 'superadmin',
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Admin test
        User::updateOrCreate(
            ['email' => 'admin2@msglink.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'MSG Link',
                'username' => 'admin',
                'email' => 'admin2@msglink.com',
                'phone' => '237600000002',
                'password' => Hash::make('Admin@123!'),
                'role' => 'admin',
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Modérateur test
        User::updateOrCreate(
            ['email' => 'moderator@msglink.com'],
            [
                'first_name' => 'Modérateur',
                'last_name' => 'MSG Link',
                'username' => 'moderator',
                'email' => 'moderator@msglink.com',
                'phone' => '237600000001',
                'password' => Hash::make('Moderator@123!'),
                'role' => 'moderator',
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $this->command->info('Team accounts seeded!');
        $this->command->info('Super Admin: admin@msglink.com / Admin@123!');
        $this->command->info('Admin: admin2@msglink.com / Admin@123!');
        $this->command->info('Moderator: moderator@msglink.com / Moderator@123!');
    }
}
