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
            if (!Schema::hasColumn('confession_comments', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('author_id')
                    ->constrained('confession_comments')
                    ->nullOnDelete();
                $table->index(['parent_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confession_comments', function (Blueprint $table) {
            if (Schema::hasColumn('confession_comments', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropIndex(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
