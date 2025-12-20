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
        Schema::create('payment_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insérer les valeurs par défaut
        DB::table('payment_configs')->insert([
            [
                'key' => 'deposit_provider',
                'value' => 'ligosapp',
                'description' => 'Provider pour les dépôts wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'withdrawal_provider',
                'value' => 'cinetpay',
                'description' => 'Provider pour les retraits wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'gift_provider',
                'value' => 'cinetpay',
                'description' => 'Provider pour les paiements de cadeaux',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'premium_provider',
                'value' => 'cinetpay',
                'description' => 'Provider pour les abonnements premium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_configs');
    }
};
