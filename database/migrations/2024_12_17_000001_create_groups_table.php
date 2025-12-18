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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->string('invite_code', 8)->unique();
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('max_members')->default(50);
            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('messages_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('invite_code');
            $table->index('creator_id');
            $table->index('is_public');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
