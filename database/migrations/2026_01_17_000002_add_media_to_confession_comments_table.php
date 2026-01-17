<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confession_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('confession_comments', 'media_url')) {
                $table->string('media_url')->nullable()->after('content');
            }
            if (!Schema::hasColumn('confession_comments', 'media_type')) {
                $table->string('media_type')->nullable()->after('media_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('confession_comments', function (Blueprint $table) {
            if (Schema::hasColumn('confession_comments', 'media_url')) {
                $table->dropColumn('media_url');
            }
            if (Schema::hasColumn('confession_comments', 'media_type')) {
                $table->dropColumn('media_type');
            }
        });
    }
};
