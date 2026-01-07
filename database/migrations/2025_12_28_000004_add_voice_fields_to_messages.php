<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add image and voice fields to anonymous_messages
        Schema::table('anonymous_messages', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('content');
            $table->string('voice_url')->nullable()->after('image_url');
            $table->string('voice_effect')->nullable()->after('voice_url');
            $table->integer('voice_duration')->nullable()->after('voice_effect'); // in seconds
        });

        // Add image and voice fields to chat_messages
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('content');
            $table->string('voice_url')->nullable()->after('image_url');
            $table->string('voice_effect')->nullable()->after('voice_url');
            $table->integer('voice_duration')->nullable()->after('voice_effect');
        });

        // Add voice fields to group_messages (already has image via type enum)
        Schema::table('group_messages', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('content');
            $table->string('voice_url')->nullable()->after('image_url');
            $table->string('voice_effect')->nullable()->after('voice_url');
            $table->integer('voice_duration')->nullable()->after('voice_effect');
        });
    }

    public function down(): void
    {
        Schema::table('anonymous_messages', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'voice_url', 'voice_effect', 'voice_duration']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'voice_url', 'voice_effect', 'voice_duration']);
        });

        Schema::table('group_messages', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'voice_url', 'voice_effect', 'voice_duration']);
        });
    }
};
