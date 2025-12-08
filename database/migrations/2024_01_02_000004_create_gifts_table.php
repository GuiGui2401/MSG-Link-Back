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
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon'); // URL ou nom de l'icône
            $table->string('animation')->nullable(); // Animation Lottie ou similaire
            $table->unsignedInteger('price'); // Prix en FCFA
            $table->enum('tier', ['bronze', 'silver', 'gold', 'diamond']);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('gift_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_id')->constrained()->onDelete('restrict');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedInteger('amount'); // Montant payé
            $table->unsignedInteger('platform_fee'); // Commission plateforme (5%)
            $table->unsignedInteger('net_amount'); // Montant pour le destinataire
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->text('message')->nullable(); // Message accompagnant le cadeau
            $table->timestamps();

            // Index
            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
            $table->index('status');
            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_transactions');
        Schema::dropIfExists('gifts');
    }
};
