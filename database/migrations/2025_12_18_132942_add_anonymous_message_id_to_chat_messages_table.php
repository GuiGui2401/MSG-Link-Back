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
            // Référence au message anonyme si ce message est une réponse à un message anonyme
            $table->foreignId('anonymous_message_id')
                ->nullable()
                ->after('gift_transaction_id')
                ->constrained('anonymous_messages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['anonymous_message_id']);
            $table->dropColumn('anonymous_message_id');
        });
    }
};
