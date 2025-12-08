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
        // Table des paiements
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['subscription', 'gift', 'withdrawal']);
            $table->enum('provider', ['ligosapp', 'cinetpay', 'intouch', 'manual']);
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->string('reference')->unique(); // Notre référence interne
            $table->string('provider_reference')->nullable(); // Référence du provider
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Index
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('provider_reference');
        });

        // Table des demandes de retrait
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('fee')->default(0); // Frais de retrait
            $table->unsignedInteger('net_amount'); // Montant net reçu
            $table->string('phone_number');
            $table->enum('provider', ['mtn_momo', 'orange_money', 'other'])->default('mtn_momo');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'rejected'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->timestamps();

            // Index
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Table historique du wallet
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->unsignedInteger('amount');
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->string('reference')->nullable();
            
            // Relation polymorphique - créée manuellement pour contrôler le nom de l'index
            $table->string('transactionable_type')->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();
            
            $table->timestamps();

            // Index avec nom raccourci (max 64 caractères pour MySQL)
            $table->index(['user_id', 'created_at']);
            $table->index(['transactionable_type', 'transactionable_id'], 'wallet_trans_morph_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('payments');
    }
};
