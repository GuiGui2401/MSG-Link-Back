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
        Schema::create('confessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->text('content'); // Chiffré
            $table->enum('type', ['private', 'public'])->default('private');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users');
            $table->timestamp('moderated_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_identity_revealed')->default(false);
            $table->timestamp('revealed_at')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['type', 'status', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
            $table->index(['author_id', 'created_at']);
        });

        // Table pour les likes des confessions publiques
        Schema::create('confession_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('confession_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['confession_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confession_likes');
        Schema::dropIfExists('confessions');
    }
};
