<?php

namespace App\Services\Notifications;

use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?ServiceConfiguration $config = null;

    public function __construct()
    {
        $this->config = ServiceConfiguration::getWhatsAppConfig();
    }

    /**
     * Send OTP via WhatsApp using a template
     *
     * @param string $recipient Recipient phone number (format: +237658895572)
     * @param string $otpCode The OTP code to send
     * @param string|null $templateName Optional WhatsApp template name (overrides config)
     * @param string|null $languageCode Optional template language code (overrides config)
     * @return array Response with success status and details
     */
    public function sendOtp(
        string $recipient,
        string $otpCode,
        ?string $templateName = null,
        ?string $languageCode = null
    ): array {
        try {
            // Check if service is configured
            if (!$this->config || !$this->config->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'WhatsApp service is not configured properly',
                    'data' => null
                ];
            }

            // Use provided template/language or fall back to config
            $templateName = $templateName ?: $this->config->whatsapp_template_name;
            $languageCode = $languageCode ?: $this->config->whatsapp_language;

            // Build API URL from configuration
            $baseUrl = "https://graph.facebook.com/{$this->config->whatsapp_api_version}/{$this->config->whatsapp_phone_number_id}/messages";

            // Normalize phone number to include + if missing
            if (!str_starts_with($recipient, '+')) {
                $recipient = '+' . $recipient;
            }

            // Prepare request payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => (string) $otpCode
                                ]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => 0,
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => (string) $otpCode
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            // Log request
            Log::info("Sending WhatsApp OTP to {$recipient}");
            Log::debug("WhatsApp API URL: {$baseUrl}");
            Log::debug("WhatsApp API payload template: {$templateName}");

            // Send request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config->whatsapp_api_token
            ])
            ->timeout(30)
            ->post($baseUrl, $payload);

            // Log response
            Log::info("WhatsApp API response status: {$response->status()}");
            Log::debug("WhatsApp API response: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();

                // Check for successful message acceptance
                if (isset($data['messages']) && count($data['messages']) > 0) {
                    $messageStatus = $data['messages'][0]['message_status'] ?? null;
                    $messageId = $data['messages'][0]['id'] ?? null;

                    if ($messageStatus === 'accepted' || $messageId) {
                        return [
                            'success' => true,
                            'message' => 'WhatsApp message sent successfully',
                            'message_id' => $messageId,
                            'data' => $data
                        ];
                    }
                }

                // If we can't confirm success from response
                return [
                    'success' => false,
                    'message' => 'WhatsApp message sent but status unclear',
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => "Server error: {$response->status()}",
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp message: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error: {$e->getMessage()}",
                'data' => null
            ];
        }
    }

    /**
     * Test WhatsApp connection by checking configuration
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            if (!$this->config || !$this->config->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'WhatsApp configuration is incomplete',
                    'data' => null
                ];
            }

            // Test by fetching phone number info
            $url = "https://graph.facebook.com/{$this->config->whatsapp_api_version}/{$this->config->whatsapp_phone_number_id}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->whatsapp_api_token
            ])
            ->timeout(10)
            ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'WhatsApp connection successful',
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => "Connection failed: {$response->status()} - " . $response->body(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection test failed: {$e->getMessage()}",
                'data' => null
            ];
        }
    }

    /**
     * Normalize phone number for WhatsApp (must include country code)
     *
     * @param string $phone
     * @return string
     */
    public static function normalizePhoneNumber(string $phone): string
    {
        // Remove spaces, dashes, and other non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with +
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }
}
