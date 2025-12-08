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
        // Notifications (skip if exists)
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('type');
                $table->string('title');
                $table->text('body')->nullable();
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'read_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // Signalements (skip if exists)
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
                $table->morphs('reportable'); // type + id (user, message, confession, etc.)
                $table->enum('reason', [
                    'spam',
                    'harassment',
                    'hate_speech',
                    'inappropriate_content',
                    'impersonation',
                    'other'
                ]);
                $table->text('description')->nullable();
                $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('action_taken')->nullable();
                $table->timestamps();

                // Index
                $table->index(['status', 'created_at']);
            });
        }

        // Blocages utilisateurs
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('blocked_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });

        // Logs administrateur
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // ban_user, approve_confession, process_withdrawal, etc.
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Index
            $table->index(['admin_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });

        // Codes de vérification (email, téléphone)
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['email', 'phone', 'password_reset']);
            $table->string('code', 6);
            $table->string('target'); // email ou numéro de téléphone
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'type', 'code']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('notifications');
    }
};
