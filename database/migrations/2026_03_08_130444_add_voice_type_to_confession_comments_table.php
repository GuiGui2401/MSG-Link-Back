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
            $table->enum('voice_type', ['normal', 'robot', 'alien', 'mystery', 'chipmunk'])
                ->default('normal')
                ->after('media_url')
                ->comment('Type d\'effet vocal appliqué aux commentaires audio anonymes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confession_comments', function (Blueprint $table) {
            $table->dropColumn('voice_type');
        });
    }
};
