<?php

namespace App\Services\Notifications;

use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NexahService
{
    protected ?ServiceConfiguration $config = null;

    public function __construct()
    {
        $this->config = ServiceConfiguration::getNexahConfig();
    }

    /**
     * Send SMS via Nexah API
     *
     * @param string $recipient Recipient phone number
     * @param string $message SMS content
     * @param string|null $senderId Optional sender ID (if none, uses configured sender)
     * @return array Response with success status and details
     */
    public function sendSms(
        string $recipient,
        string $message,
        ?string $senderId = null
    ): array {
        try {
            // Check if service is configured
            if (!$this->config || !$this->config->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Nexah SMS service is not configured properly',
                    'data' => null
                ];
            }

            // Build URL from configuration
            $url = $this->config->nexah_base_url . $this->config->nexah_send_endpoint;

            // Prepare request payload with config values
            $payload = [
                'user' => $this->config->nexah_user,
                'password' => $this->config->nexah_password,
                'senderid' => $senderId ?: $this->config->nexah_sender_id,
                'sms' => $message,
                'mobiles' => $recipient,
            ];

            // Log request
            Log::info("Sending SMS to {$recipient}");
            Log::debug("Nexah API URL: {$url}");
            Log::debug("Nexah API payload user: {$payload['user']}, sender: {$payload['senderid']}");

            // Send request
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($url, $payload);

            // Log response
            Log::info("Nexah API response: {$response->status()}");
            Log::debug("Nexah API response text: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();

                // Parse different success response formats
                $isSuccess = false;
                $responseMessage = 'SMS sending failed';

                // Check various success indicators
                if (isset($data['status']) && in_array($data['status'], ['success', 'ok'])) {
                    $isSuccess = true;
                    $responseMessage = 'SMS sent successfully!';
                } elseif (isset($data['sent']) && $data['sent'] === true) {
                    $isSuccess = true;
                    $responseMessage = 'SMS sent successfully!';
                } elseif (isset($data['error']) && $data['error'] === false) {
                    $isSuccess = true;
                    $responseMessage = 'SMS sent successfully!';
                } elseif (isset($data['response']) && (
                    $data['response'] === 'OK' ||
                    stripos((string)$data['response'], 'success') !== false
                )) {
                    $isSuccess = true;
                    $responseMessage = 'SMS sent successfully!';
                } elseif (
                    stripos(json_encode($data), 'success') !== false ||
                    stripos(json_encode($data), 'ok') !== false
                ) {
                    $isSuccess = true;
                    $responseMessage = 'SMS sent successfully!';
                }

                return [
                    'success' => $isSuccess,
                    'message' => $responseMessage,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => "Server error: {$response->status()}",
                'data' => null
            ];

        } catch (\Exception $e) {
            Log::error("Error sending SMS: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error: {$e->getMessage()}",
                'data' => null
            ];
        }
    }

    /**
     * Get account information including credit balance
     *
     * @return array Account information including credit balance
     */
    public function getAccountInfo(): array
    {
        try {
            // Check if service is configured
            if (!$this->config || !$this->config->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Nexah SMS service is not configured properly',
                    'data' => null
                ];
            }

            // Build URL from configuration
            $url = $this->config->nexah_base_url . $this->config->nexah_credits_endpoint;

            // Prepare request payload
            $payload = [
                'user' => $this->config->nexah_user,
                'password' => $this->config->nexah_password,
            ];

            // Send request
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($url, $payload);

            Log::info("Account info response: {$response->status()}");

            if ($response->successful()) {
                $data = $response->json();

                $credit = null;

                // Extract credit balance from different response structures
                if (is_array($data)) {
                    if (isset($data['credit'])) {
                        $credit = $data['credit'];
                    } elseif (isset($data['data']) && is_array($data['data'])) {
                        $credit = $data['data']['credit'] ?? $data['data']['balance'] ?? null;
                    } elseif (isset($data['response']) && is_array($data['response'])) {
                        $credit = $data['response']['credit'] ?? $data['response']['balance'] ?? null;
                    }
                }

                return [
                    'success' => true,
                    'credit' => $credit,
                    'user' => $this->config->nexah_user,
                    'sender_id' => $this->config->nexah_sender_id,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => "Server error: {$response->status()}",
                'data' => null
            ];

        } catch (\Exception $e) {
            Log::error("Error getting account info: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error: {$e->getMessage()}",
                'data' => null
            ];
        }
    }

    /**
     * Test Nexah connection by getting account info
     *
     * @return array
     */
    public function testConnection(): array
    {
        return $this->getAccountInfo();
    }
}
