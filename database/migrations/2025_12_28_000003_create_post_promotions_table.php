<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('confession_id')->constrained('confessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->integer('duration_hours')->default(24);
            $table->integer('reach_boost')->default(100); // percentage boost
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active'); // active, expired, cancelled
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->timestamps();

            $table->index(['confession_id', 'status']);
            $table->index(['ends_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_promotions');
    }
};
