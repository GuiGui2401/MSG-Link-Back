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
        Schema::create('confession_identity_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('User who revealed the identity')->constrained()->onDelete('cascade');
            $table->foreignId('confession_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure a user can reveal an identity only once
            $table->unique(['user_id', 'confession_id']);

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('confession_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confession_identity_reveals');
    }
};
