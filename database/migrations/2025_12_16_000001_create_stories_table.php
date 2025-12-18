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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video', 'text'])->default('image');
            $table->string('media_url')->nullable();
            $table->text('content')->nullable(); // Pour les stories texte
            $table->string('thumbnail_url')->nullable(); // Miniature pour vidéos
            $table->string('background_color')->nullable(); // Pour stories texte
            $table->unsignedInteger('duration')->default(5); // Durée d'affichage en secondes
            $table->unsignedInteger('views_count')->default(0);
            $table->enum('status', ['active', 'expired'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['user_id', 'status', 'expires_at']);
            $table->index(['status', 'created_at']);
        });

        // Table pour les vues des stories
        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['story_id', 'user_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('stories');
    }
};
