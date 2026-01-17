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
        Schema::create('monetization_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['creator_fund', 'ad_revenue']);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('engagement_score')->default(0);
            $table->unsignedBigInteger('total_engagement_score')->default(0);
            $table->unsignedInteger('amount')->default(0);
            $table->enum('status', ['pending', 'paid', 'skipped'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'period_start', 'period_end']);
            $table->index(['type', 'period_start', 'period_end']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monetization_payouts');
    }
};
