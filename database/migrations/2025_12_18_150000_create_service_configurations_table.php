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
        Schema::create('service_configurations', function (Blueprint $table) {
            $table->id();

            // Type de service (whatsapp, sms, payment)
            $table->string('service_type')->unique(); // 'whatsapp', 'nexah_sms', 'freemopay'
            $table->boolean('is_active')->default(true);

            // Configuration JSON pour flexibilité
            $table->json('config')->nullable();

            // WhatsApp specific fields (for easier access)
            $table->string('whatsapp_api_token')->nullable();
            $table->string('whatsapp_phone_number_id')->nullable();
            $table->string('whatsapp_api_version')->default('v21.0');
            $table->string('whatsapp_template_name')->nullable();
            $table->string('whatsapp_language')->default('fr');

            // Nexah SMS specific fields
            $table->string('nexah_base_url')->nullable();
            $table->string('nexah_send_endpoint')->default('/sms/1/text/single');
            $table->string('nexah_credits_endpoint')->default('/account/1/balance');
            $table->string('nexah_user')->nullable();
            $table->string('nexah_password')->nullable();
            $table->string('nexah_sender_id')->nullable();

            // FreeMoPay specific fields
            $table->string('freemopay_base_url')->default('https://api-v2.freemopay.com');
            $table->string('freemopay_app_key')->nullable();
            $table->string('freemopay_secret_key')->nullable();
            $table->string('freemopay_callback_url')->nullable();
            $table->integer('freemopay_init_payment_timeout')->default(5);
            $table->integer('freemopay_status_check_timeout')->default(5);
            $table->integer('freemopay_token_timeout')->default(10);
            $table->integer('freemopay_token_cache_duration')->default(3000); // 50 minutes
            $table->integer('freemopay_max_retries')->default(2);
            $table->decimal('freemopay_retry_delay', 3, 1)->default(0.5);

            // Notification preferences
            $table->enum('default_notification_channel', ['whatsapp', 'sms'])->default('sms');

            $table->timestamps();

            // Index pour les recherches fréquentes
            $table->index('service_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_configurations');
    }
};
