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
            ['email' => 'admin@weylo.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'superadmin',
                'email' => 'admin@weylo.com',
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
            ['email' => 'admin2@weylo.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Weylo',
                'username' => 'admin',
                'email' => 'admin2@weylo.com',
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
            ['email' => 'moderator@weylo.com'],
            [
                'first_name' => 'Modérateur',
                'last_name' => 'Weylo',
                'username' => 'moderator',
                'email' => 'moderator@weylo.com',
                'phone' => '237600000001',
                'password' => Hash::make('Moderator@123!'),
                'role' => 'moderator',
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        $this->command->info('Team accounts seeded!');
        $this->command->info('Super Admin: admin@weylo.com / Admin@123!');
        $this->command->info('Admin: admin2@weylo.com / Admin@123!');
        $this->command->info('Moderator: moderator@weylo.com / Moderator@123!');
    }
}
