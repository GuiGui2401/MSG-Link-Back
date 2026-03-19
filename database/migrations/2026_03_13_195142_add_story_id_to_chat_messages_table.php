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
        Schema::table('chat_messages', function (Blueprint $table) {
            // Ajouter la colonne story_id après anonymous_message_id
            $table->foreignId('story_id')
                ->nullable()
                ->after('anonymous_message_id')
                ->constrained('stories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['story_id']);
            $table->dropColumn('story_id');
        });
    }
};
