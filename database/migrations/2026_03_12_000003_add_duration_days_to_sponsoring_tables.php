<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_packages', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(7)->after('price');
        });

        Schema::table('sponsorships', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(7)->after('reach_max');
            $table->timestamp('ends_at')->nullable()->after('duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_packages', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });

        Schema::table('sponsorships', function (Blueprint $table) {
            $table->dropColumn(['duration_days', 'ends_at']);
        });
    }
};

