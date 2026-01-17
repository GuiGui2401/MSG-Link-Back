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
        Schema::table('confessions', function (Blueprint $table) {
            if (!Schema::hasColumn('confessions', 'video')) {
                $table->string('video')->nullable()->after('image');
            }
            if (!Schema::hasColumn('confessions', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(false)->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confessions', function (Blueprint $table) {
            if (Schema::hasColumn('confessions', 'video')) {
                $table->dropColumn('video');
            }
            if (Schema::hasColumn('confessions', 'is_anonymous')) {
                $table->dropColumn('is_anonymous');
            }
        });
    }
};
