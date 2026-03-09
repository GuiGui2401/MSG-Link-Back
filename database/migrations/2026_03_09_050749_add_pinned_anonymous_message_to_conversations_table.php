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
        Schema::table('conversations', function (Blueprint $table) {
            // Épingler un message anonyme dans la conversation (pour contexte)
            $table->foreignId('pinned_anonymous_message_id')
                ->nullable()
                ->after('id')
                ->constrained('anonymous_messages')
                ->onDelete('set null');

            $table->index('pinned_anonymous_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['pinned_anonymous_message_id']);
            $table->dropIndex(['pinned_anonymous_message_id']);
            $table->dropColumn('pinned_anonymous_message_id');
        });
    }
};
