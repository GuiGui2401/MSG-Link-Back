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
            $table->enum('media_type', ['none', 'image', 'video'])->default('none')->after('content');
            $table->string('media_url')->nullable()->after('media_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confessions', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_url']);
        });
    }
};
