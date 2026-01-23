<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confession_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('confession_comments', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('media_type');
            }
        });

        Schema::create('confession_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')
                ->constrained('confession_comments')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confession_comment_likes');

        Schema::table('confession_comments', function (Blueprint $table) {
            if (Schema::hasColumn('confession_comments', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
        });
    }
};
