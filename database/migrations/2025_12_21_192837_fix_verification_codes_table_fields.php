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
        Schema::table('verification_codes', function (Blueprint $table) {
            // Renommer verified_at en used_at pour correspondre au modèle
            $table->renameColumn('verified_at', 'used_at');

            // Supprimer les colonnes inutilisées
            $table->dropColumn(['target', 'attempts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verification_codes', function (Blueprint $table) {
            // Rétablir l'ancien état
            $table->renameColumn('used_at', 'verified_at');

            // Recréer les colonnes supprimées
            $table->string('target')->after('code');
            $table->unsignedTinyInteger('attempts')->default(0)->after('expires_at');
        });
    }
};
