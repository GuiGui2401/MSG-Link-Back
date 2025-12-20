<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Champs pour le passe premium global
            $table->boolean('is_premium')->default(false)->after('is_verified');
            $table->timestamp('premium_started_at')->nullable()->after('is_premium');
            $table->timestamp('premium_expires_at')->nullable()->after('premium_started_at');
            $table->boolean('premium_auto_renew')->default(false)->after('premium_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_premium',
                'premium_started_at',
                'premium_expires_at',
                'premium_auto_renew',
            ]);
        });
    }
};
