<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('subscription', 'gift', 'withdrawal', 'reveal_identity', 'deposit') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('subscription', 'gift', 'withdrawal', 'deposit') NOT NULL");
    }
};
