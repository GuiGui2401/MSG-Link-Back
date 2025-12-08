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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_one_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('participant_two_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            
            // Système Flame (streak)
            $table->unsignedInteger('streak_count')->default(0);
            $table->timestamp('streak_updated_at')->nullable();
            $table->enum('flame_level', ['none', 'yellow', 'orange', 'purple'])->default('none');
            
            // Compteurs
            $table->unsignedInteger('message_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();

            // Contrainte unique pour éviter les doublons
            $table->unique(['participant_one_id', 'participant_two_id']);
            
            // Index
            $table->index('last_message_at');
            $table->index(['participant_one_id', 'last_message_at']);
            $table->index(['participant_two_id', 'last_message_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->nullable(); // Chiffré, nullable si c'est un cadeau
            $table->enum('type', ['text', 'gift', 'system'])->default('text');
            $table->foreignId('gift_transaction_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        // Table pour gérer quel participant a supprimé la conversation de son côté
        Schema::create('conversation_deletions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('deleted_at');

            $table->unique(['conversation_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_deletions');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversations');
    }
};
