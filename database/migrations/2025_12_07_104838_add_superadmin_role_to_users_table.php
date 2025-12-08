<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'ENUM pour inclure superadmin
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'moderator', 'admin', 'superadmin') DEFAULT 'user'");

        // Promouvoir l'admin actuel en superadmin
        DB::table('users')
            ->where('email', 'admin@msglink.com')
            ->update(['role' => 'superadmin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rétrograder les superadmins en admins avant de supprimer le rôle
        DB::table('users')
            ->where('role', 'superadmin')
            ->update(['role' => 'admin']);

        // Revenir à l'ancien ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'moderator', 'admin') DEFAULT 'user'");
    }
};
