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
            // Ajouter le support des réponses (tag du message original)
            $table->foreignId('reply_to_message_id')
                ->nullable()
                ->after('recipient_id')
                ->constrained('anonymous_messages')
                ->onDelete('cascade');

            // Index pour retrouver rapidement les réponses à un message
            $table->index('reply_to_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anonymous_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropColumn('reply_to_message_id');
        });
    }
};
