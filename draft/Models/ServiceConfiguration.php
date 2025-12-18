<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ServiceConfiguration extends Model
{
    protected $fillable = [
        'service_type',
        'is_active',
        'config',

        // WhatsApp
        'whatsapp_api_token',
        'whatsapp_phone_number_id',
        'whatsapp_api_version',
        'whatsapp_template_name',
        'whatsapp_language',

        // Nexah SMS
        'nexah_base_url',
        'nexah_send_endpoint',
        'nexah_credits_endpoint',
        'nexah_user',
        'nexah_password',
        'nexah_sender_id',

        // FreeMoPay
        'freemopay_base_url',
        'freemopay_app_key',
        'freemopay_secret_key',
        'freemopay_callback_url',
        'freemopay_init_payment_timeout',
        'freemopay_status_check_timeout',
        'freemopay_token_timeout',
        'freemopay_token_cache_duration',
        'freemopay_max_retries',
        'freemopay_retry_delay',

        // Preferences
        'default_notification_channel',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'freemopay_init_payment_timeout' => 'integer',
        'freemopay_status_check_timeout' => 'integer',
        'freemopay_token_timeout' => 'integer',
        'freemopay_token_cache_duration' => 'integer',
        'freemopay_max_retries' => 'integer',
        'freemopay_retry_delay' => 'decimal:1',
    ];

    /**
     * Get configuration for a specific service
     *
     * @param string $serviceType
     * @return self|null
     */
    public static function getConfig(string $serviceType): ?self
    {
        return Cache::remember(
            "service_config_{$serviceType}",
            now()->addHours(1),
            fn() => self::where('service_type', $serviceType)->first()
        );
    }

    /**
     * Get WhatsApp configuration
     */
    public static function getWhatsAppConfig(): ?self
    {
        return self::getConfig('whatsapp');
    }

    /**
     * Get Nexah SMS configuration
     */
    public static function getNexahConfig(): ?self
    {
        return self::getConfig('nexah_sms');
    }

    /**
     * Get FreeMoPay configuration
     */
    public static function getFreeMoPayConfig(): ?self
    {
        return self::getConfig('freemopay');
    }

    /**
     * Get default notification channel
     */
    public static function getDefaultNotificationChannel(): string
    {
        $config = Cache::remember(
            'default_notification_channel',
            now()->addHours(1),
            function () {
                $config = self::where('service_type', 'notification_preferences')->first();
                return $config?->default_notification_channel ?? 'sms';
            }
        );

        return $config;
    }

    /**
     * Clear configuration cache
     */
    public static function clearCache(?string $serviceType = null): void
    {
        if ($serviceType) {
            Cache::forget("service_config_{$serviceType}");
        } else {
            // Clear all service configs
            Cache::forget('service_config_whatsapp');
            Cache::forget('service_config_nexah_sms');
            Cache::forget('service_config_freemopay');
            Cache::forget('service_config_notification_preferences');
            Cache::forget('default_notification_channel');
        }
    }

    /**
     * Boot method to clear cache on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($config) {
            self::clearCache($config->service_type);
        });

        static::deleted(function ($config) {
            self::clearCache($config->service_type);
        });
    }

    /**
     * Validate WhatsApp configuration
     */
    public function validateWhatsAppConfig(): array
    {
        $errors = [];

        if (empty($this->whatsapp_api_token)) {
            $errors[] = 'WhatsApp API Token is required';
        }

        if (empty($this->whatsapp_phone_number_id)) {
            $errors[] = 'WhatsApp Phone Number ID is required';
        }

        if (empty($this->whatsapp_template_name)) {
            $errors[] = 'WhatsApp Template Name is required';
        }

        return $errors;
    }

    /**
     * Validate Nexah SMS configuration
     */
    public function validateNexahConfig(): array
    {
        $errors = [];

        if (empty($this->nexah_base_url)) {
            $errors[] = 'Nexah Base URL is required';
        }

        if (empty($this->nexah_user)) {
            $errors[] = 'Nexah User is required';
        }

        if (empty($this->nexah_password)) {
            $errors[] = 'Nexah Password is required';
        }

        if (empty($this->nexah_sender_id)) {
            $errors[] = 'Nexah Sender ID is required';
        }

        return $errors;
    }

    /**
     * Validate FreeMoPay configuration
     */
    public function validateFreeMoPayConfig(): array
    {
        $errors = [];

        if (empty($this->freemopay_app_key)) {
            $errors[] = 'FreeMoPay App Key is required';
        }

        if (empty($this->freemopay_secret_key)) {
            $errors[] = 'FreeMoPay Secret Key is required';
        }

        if (empty($this->freemopay_callback_url)) {
            $errors[] = 'FreeMoPay Callback URL is required';
        }

        // Validate callback URL format
        if ($this->freemopay_callback_url && !filter_var($this->freemopay_callback_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'FreeMoPay Callback URL must be a valid URL';
        }

        // Check HTTPS in production
        if ($this->freemopay_callback_url &&
            config('app.env') === 'production' &&
            !str_starts_with($this->freemopay_callback_url, 'https://')) {
            $errors[] = 'FreeMoPay Callback URL must use HTTPS in production';
        }

        return $errors;
    }

    /**
     * Check if service is properly configured and active
     */
    public function isConfigured(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $errors = match($this->service_type) {
            'whatsapp' => $this->validateWhatsAppConfig(),
            'nexah_sms' => $this->validateNexahConfig(),
            'freemopay' => $this->validateFreeMoPayConfig(),
            default => [],
        };

        return empty($errors);
    }
}
