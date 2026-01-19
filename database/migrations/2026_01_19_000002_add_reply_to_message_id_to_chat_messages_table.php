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
            if (!Schema::hasColumn('chat_messages', 'reply_to_message_id')) {
                $table->foreignId('reply_to_message_id')
                    ->nullable()
                    ->after('anonymous_message_id')
                    ->constrained('chat_messages')
                    ->nullOnDelete();
                $table->index(['reply_to_message_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'reply_to_message_id')) {
                $table->dropForeign(['reply_to_message_id']);
                $table->dropIndex(['reply_to_message_id']);
                $table->dropColumn('reply_to_message_id');
            }
        });
    }
};
