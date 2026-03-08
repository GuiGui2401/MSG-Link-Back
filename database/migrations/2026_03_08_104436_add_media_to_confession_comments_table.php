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
        Schema::table('confession_comments', function (Blueprint $table) {
            $table->enum('media_type', ['none', 'audio', 'image'])->default('none')->after('content');
            $table->string('media_url', 500)->nullable()->after('media_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confession_comments', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_url']);
        });
    }
};
