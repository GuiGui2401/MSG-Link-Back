<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_promotions', function (Blueprint $table) {
            $table->string('goal')->nullable();
            $table->string('sub_goal')->nullable();
            $table->string('audience_mode')->nullable();
            $table->string('gender')->nullable();
            $table->string('age_range')->nullable();
            $table->json('locations')->nullable();
            $table->json('interests')->nullable();
            $table->string('language')->nullable();
            $table->string('device_type')->nullable();
            $table->string('budget_mode')->nullable();
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->decimal('total_budget', 10, 2)->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('branded_content')->default(false);
            $table->string('payment_method')->nullable();
            $table->decimal('estimated_views', 12, 2)->nullable();
            $table->decimal('estimated_reach', 12, 2)->nullable();
            $table->decimal('estimated_cpv', 12, 2)->nullable();
            $table->uuid('campaign_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('post_promotions', function (Blueprint $table) {
            $table->dropColumn([
                'goal',
                'sub_goal',
                'audience_mode',
                'gender',
                'age_range',
                'locations',
                'interests',
                'language',
                'device_type',
                'budget_mode',
                'daily_budget',
                'total_budget',
                'duration_days',
                'cta_label',
                'website_url',
                'branded_content',
                'payment_method',
                'estimated_views',
                'estimated_reach',
                'estimated_cpv',
                'campaign_id',
            ]);
        });
    }
};
