<?php

namespace App\Services\Notifications;

use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected WhatsAppService $whatsappService;
    protected NexahService $nexahService;

    public function __construct()
    {
        $this->whatsappService = new WhatsAppService();
        $this->nexahService = new NexahService();
    }

    /**
     * Send OTP code using the configured default channel
     *
     * @param string $recipient Recipient phone number
     * @param string $otpCode The OTP code to send
     * @param string|null $message Optional custom message (used for SMS only)
     * @return array Response with success status and details
     */
    public function sendOtp(
        string $recipient,
        string $otpCode,
        ?string $message = null
    ): array {
        // Get current configuration
        $defaultChannel = ServiceConfiguration::getDefaultNotificationChannel();

        // Default SMS message if none provided
        if (!$message) {
            $message = "Votre code de vérification Weylo est : '{$otpCode}'. Il expire dans 5 minutes.";
        }

        // Choose the appropriate service based on configuration
        if ($defaultChannel === 'whatsapp') {
            Log::info("Sending OTP via WhatsApp to {$recipient}");

            $result = $this->whatsappService->sendOtp($recipient, $otpCode);

            // Fallback to SMS if WhatsApp fails
            if (!$result['success']) {
                Log::warning("WhatsApp failed, falling back to SMS");
                return $this->nexahService->sendSms($recipient, $message);
            }

            return $result;
        } else {
            // Default to SMS
            Log::info("Sending OTP via SMS to {$recipient}");

            $result = $this->nexahService->sendSms($recipient, $message);

            // Fallback to WhatsApp if SMS fails
            if (!$result['success']) {
                Log::warning("SMS failed, falling back to WhatsApp");
                return $this->whatsappService->sendOtp($recipient, $otpCode);
            }

            return $result;
        }
    }

    /**
     * Send notification via WhatsApp only
     *
     * @param string $recipient
     * @param string $otpCode
     * @return array
     */
    public function sendViaWhatsApp(string $recipient, string $otpCode): array
    {
        return $this->whatsappService->sendOtp($recipient, $otpCode);
    }

    /**
     * Send notification via SMS only
     *
     * @param string $recipient
     * @param string $message
     * @return array
     */
    public function sendViaSms(string $recipient, string $message): array
    {
        return $this->nexahService->sendSms($recipient, $message);
    }

    /**
     * Send a custom message (not OTP) via the default channel
     *
     * @param string $recipient
     * @param string $message
     * @return array
     */
    public function send(string $recipient, string $message): array
    {
        $defaultChannel = ServiceConfiguration::getDefaultNotificationChannel();

        if ($defaultChannel === 'whatsapp') {
            // For WhatsApp, we can only use templates, so we fallback to SMS for custom messages
            Log::info("Custom message requested, using SMS instead of WhatsApp (template limitation)");
            return $this->nexahService->sendSms($recipient, $message);
        } else {
            return $this->nexahService->sendSms($recipient, $message);
        }
    }
}
