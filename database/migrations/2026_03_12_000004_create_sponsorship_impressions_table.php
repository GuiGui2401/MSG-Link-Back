<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsorship_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsorship_id')->constrained('sponsorships')->cascadeOnDelete();
            $table->foreignId('viewer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sponsorship_id', 'viewer_id']);
            $table->index(['viewer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_impressions');
    }
};

