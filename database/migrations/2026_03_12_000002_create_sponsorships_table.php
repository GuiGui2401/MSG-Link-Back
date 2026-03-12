<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsorship_package_id')->constrained('sponsorship_packages')->cascadeOnDelete();

            $table->string('media_type'); // text|image|video
            $table->text('text_content')->nullable();
            $table->string('media_url')->nullable();

            $table->unsignedInteger('price');
            $table->unsignedInteger('reach_min');
            $table->unsignedInteger('reach_max')->nullable();

            $table->string('status')->default('active')->index(); // active|paused|completed|cancelled
            $table->unsignedInteger('delivered_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorships');
    }
};

