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
        Schema::create('conversation_identity_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Celui qui paie pour révéler
            $table->foreignId('revealed_user_id')->constrained('users')->onDelete('cascade'); // Celui dont l'identité est révélée
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->onDelete('set null');
            $table->timestamp('revealed_at');
            $table->timestamps();

            // Un utilisateur ne peut révéler l'identité qu'une seule fois par conversation
            $table->unique(['conversation_id', 'user_id', 'revealed_user_id'], 'conv_id_reveals_unique');

            // Index
            $table->index(['conversation_id', 'user_id']);
            $table->index('revealed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_identity_reveals');
    }
};
