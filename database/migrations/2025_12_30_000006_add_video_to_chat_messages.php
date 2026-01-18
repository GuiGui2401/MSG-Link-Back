<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'video_url')) {
                $table->string('video_url')->nullable()->after('voice_url');
            }
        });

        DB::statement(
            "ALTER TABLE chat_messages MODIFY COLUMN type ENUM('text','gift','system','image','voice','video') NOT NULL DEFAULT 'text'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE chat_messages MODIFY COLUMN type ENUM('text','gift','system') NOT NULL DEFAULT 'text'"
        );

        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'video_url')) {
                $table->dropColumn('video_url');
            }
        });
    }
};
