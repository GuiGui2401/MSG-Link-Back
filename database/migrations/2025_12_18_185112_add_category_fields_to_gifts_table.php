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
        Schema::table('gifts', function (Blueprint $table) {
            $table->foreignId('gift_category_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->string('background_color')->default('#FF6B6B')->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropForeign(['gift_category_id']);
            $table->dropColumn(['gift_category_id', 'background_color']);
        });
    }
};
