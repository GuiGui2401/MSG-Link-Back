<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create permissions
        $permissions = [
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            'ban users',

            // Confessions/Posts
            'view confessions',
            'edit confessions',
            'delete confessions',
            'moderate confessions',

            // Messages
            'view messages',
            'delete messages',

            // Gifts
            'manage gifts',
            'view gift transactions',

            // Reports
            'view reports',
            'handle reports',

            // Settings
            'manage settings',

            // Premium
            'manage premium',
            'grant premium',

            // Analytics
            'view analytics',

            // Payments
            'view payments',
            'manage payments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to super-admin
        $superAdminRole->syncPermissions(Permission::all());

        // Assign specific permissions to admin
        $adminRole->syncPermissions([
            'view users',
            'edit users',
            'ban users',
            'view confessions',
            'edit confessions',
            'delete confessions',
            'moderate confessions',
            'view messages',
            'delete messages',
            'manage gifts',
            'view gift transactions',
            'view reports',
            'handle reports',
            'view analytics',
            'view payments',
        ]);

        // Assign specific permissions to moderator
        $moderatorRole->syncPermissions([
            'view users',
            'view confessions',
            'moderate confessions',
            'view reports',
            'handle reports',
        ]);

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@weylo.app'],
            [
                'username' => 'admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_premium' => true,
                'role' => 'superadmin', // Required for dashboard login
            ]
        );

        // Update role if user already exists
        $superAdmin->update(['role' => 'superadmin']);
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('Admin seeder completed successfully!');
        $this->command->info('Super Admin credentials:');
        $this->command->info('Email: admin@weylo.app');
        $this->command->info('Password: password');
    }
}
