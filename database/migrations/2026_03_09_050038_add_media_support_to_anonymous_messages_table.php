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
        Schema::table('anonymous_messages', function (Blueprint $table) {
            // Support pour les médias (audio, image)
            $table->enum('media_type', ['none', 'audio', 'image'])
                ->default('none')
                ->after('content');

            // URL du fichier média (audio ou image)
            $table->string('media_url')->nullable()->after('media_type');

            // Type de voix pour les messages audio (si anonyme)
            $table->enum('voice_type', ['normal', 'robot', 'alien', 'mystery', 'chipmunk'])
                ->nullable()
                ->after('media_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anonymous_messages', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_url', 'voice_type']);
        });
    }
};
