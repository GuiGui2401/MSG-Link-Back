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
        Schema::create('group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->nullable(); // Chiffré
            $table->enum('type', ['text', 'image', 'system'])->default('text');
            $table->foreignId('reply_to_message_id')->nullable()->constrained('group_messages')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['group_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_messages');
    }
};
