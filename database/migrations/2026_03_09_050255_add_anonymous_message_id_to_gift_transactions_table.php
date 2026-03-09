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
        Schema::table('gift_transactions', function (Blueprint $table) {
            // Support pour les cadeaux envoyés via messages anonymes
            $table->foreignId('anonymous_message_id')
                ->nullable()
                ->after('conversation_id')
                ->constrained('anonymous_messages')
                ->onDelete('cascade');

            // Index pour retrouver rapidement les cadeaux d'un message anonyme
            $table->index('anonymous_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_transactions', function (Blueprint $table) {
            $table->dropForeign(['anonymous_message_id']);
            $table->dropIndex(['anonymous_message_id']);
            $table->dropColumn('anonymous_message_id');
        });
    }
};
