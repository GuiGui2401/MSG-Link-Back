<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->string('type')->default('text'); // text, voice, image, emoji
            $table->string('media_url')->nullable();
            $table->string('voice_effect')->nullable(); // pitch_up, pitch_down, robot, chipmunk, deep
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();

            $table->index('story_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_replies');
    }
};
