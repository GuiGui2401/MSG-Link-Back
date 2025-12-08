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
        Schema::create('premium_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
            
            // Type d'abonnement : par conversation ou par message spécifique
            $table->enum('type', ['conversation', 'message']);
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->nullable(); // ID du message anonyme si type = message
            
            $table->unsignedInteger('amount'); // Montant payé
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->string('payment_reference')->nullable();
            
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            
            $table->timestamps();

            // Index
            $table->index(['subscriber_id', 'status']);
            $table->index(['target_user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('payment_reference');
            
            // Un utilisateur ne peut avoir qu'un seul abonnement actif par conversation
            $table->unique(['subscriber_id', 'conversation_id', 'status'], 'unique_active_conversation_sub');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premium_subscriptions');
    }
};
