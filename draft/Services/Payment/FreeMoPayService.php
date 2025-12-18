<?php

namespace App\Services\Payment;

use App\Models\ServiceConfiguration;
use App\Models\Payment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FreeMoPayService
{
    protected ?ServiceConfiguration $config = null;
    protected FreeMoPayClient $client;
    protected FreeMoPayTokenManager $tokenManager;

    public function __construct()
    {
        $this->config = ServiceConfiguration::getFreeMoPayConfig();
        $this->client = new FreeMoPayClient();
        $this->tokenManager = new FreeMoPayTokenManager($this->client);
    }

    /**
     * Initialize a payment with FreeMoPay
     *
     * @param User|Company $payer The entity making the payment
     * @param float $amount Payment amount
     * @param string $phoneNumber Payer's phone number (237XXXXXXXXX or 243XXXXXXXXX)
     * @param string $description Payment description
     * @param string|null $externalId Optional external ID
     * @return Payment
     * @throws \Exception
     */
    public function initPayment(
        $payer,
        float $amount,
        string $phoneNumber,
        string $description,
        ?string $externalId = null
    ): Payment {
        if (!$this->config || !$this->config->isConfigured()) {
            throw new \Exception('FreeMoPay service is not configured properly');
        }

        Log::info("[FreeMoPay Service] Initiating payment - Amount: {$amount}, Phone: {$phoneNumber}");

        // 1. Validate phone number
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // 2. Generate external ID if not provided
        if (!$externalId) {
            $externalId = $this->generateExternalId();
        }

        // Ensure unique external ID
        $externalId = $this->ensureUniqueExternalId($externalId);

        // 3. Get callback URL
        $callbackUrl = $this->config->freemopay_callback_url;

        Log::info("[FreeMoPay Service] Callback URL: {$callbackUrl}, External ID: {$externalId}");

        // 4. Create Payment record in database (status: pending)
        $payment = DB::transaction(function () use ($payer, $amount, $normalizedPhone, $description, $externalId) {
            $paymentData = [
                'amount' => $amount,
                'phone_number' => $normalizedPhone,
                'description' => $description,
                'external_id' => $externalId,
                'status' => 'pending',
                'provider' => 'freemopay',
            ];

            // Add payer information based on type
            if ($payer instanceof Company) {
                $paymentData['company_id'] = $payer->id;
            } elseif ($payer instanceof User) {
                $paymentData['user_id'] = $payer->id;
            }

            return Payment::create($paymentData);
        });

        Log::info("[FreeMoPay Service] Payment record created - ID: {$payment->id}");

        // 5. Call FreeMoPay API
        try {
            $freemoResponse = $this->callFreeMoPayAPI(
                $normalizedPhone,
                $amount,
                $externalId,
                $description,
                $callbackUrl
            );

            // 6. Update payment with FreeMoPay reference
            $reference = $freemoResponse['reference'] ?? null;

            if (!$reference) {
                Log::error("[FreeMoPay Service] No reference in response: " . json_encode($freemoResponse));
                $payment->update(['status' => 'failed']);
                throw new \Exception('No reference in FreeMoPay response');
            }

            $payment->update([
                'reference' => $reference,
                'provider_response' => $freemoResponse,
            ]);

            Log::info("[FreeMoPay Service] Payment initiated successfully - Reference: {$reference}, Payment ID: {$payment->id}");

            return $payment;

        } catch (\Exception $e) {
            // Mark payment as failed
            $payment->update(['status' => 'failed']);

            Log::error("[FreeMoPay Service] Payment initiation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Call FreeMoPay API to initialize payment
     *
     * @param string $payer
     * @param float $amount
     * @param string $externalId
     * @param string $description
     * @param string $callback
     * @return array
     * @throws \Exception
     */
    protected function callFreeMoPayAPI(
        string $payer,
        float $amount,
        string $externalId,
        string $description,
        string $callback
    ): array {
        // Get Bearer token
        $bearerToken = $this->tokenManager->getToken();

        // Prepare payload
        $payload = [
            'payer' => $payer,
            'amount' => $amount,
            'externalId' => $externalId,
            'description' => $description,
            'callback' => $callback
        ];

        Log::info("[FreeMoPay Service] Calling FreeMoPay API");
        Log::info("[FreeMoPay Service] Payload: " . json_encode($payload));
        Log::info("[FreeMoPay Service] URL: {$this->config->freemopay_base_url}/payment");

        // POST /payment
        $response = $this->client->post(
            '/payment',
            $payload,
            $bearerToken,
            false,
            $this->config->freemopay_init_payment_timeout
        );

        Log::info("[FreeMoPay Service] FreeMoPay response: " . json_encode($response));

        // Check init status
        $initStatus = $response['status'] ?? null;

        // Valid init statuses
        $validInitStatuses = ['SUCCESS', 'CREATED', 'PENDING', 'PROCESSING'];

        // Failed statuses
        $failedStatuses = ['FAILED', 'FAILURE', 'ERROR', 'REJECTED', 'CANCELLED', 'CANCELED'];

        if (in_array($initStatus, $failedStatuses)) {
            $errorMessage = $response['message'] ?? 'Unknown error';
            Log::error("[FreeMoPay Service] Init failed - Status: {$initStatus}, Message: {$errorMessage}");
            throw new \Exception("Payment initialization failed: {$errorMessage}");
        }

        if (!in_array($initStatus, $validInitStatuses)) {
            Log::warning("[FreeMoPay Service] Unknown init status: {$initStatus}, treating as success");
        }

        return $response;
    }

    /**
     * Check payment status
     *
     * @param string $reference FreeMoPay reference
     * @return array
     * @throws \Exception
     */
    public function checkPaymentStatus(string $reference): array
    {
        if (!$this->config || !$this->config->isConfigured()) {
            throw new \Exception('FreeMoPay service is not configured properly');
        }

        Log::info("[FreeMoPay Service] Checking payment status - Reference: {$reference}");

        // Get Bearer token
        $bearerToken = $this->tokenManager->getToken();

        // GET /payment/{reference}
        $response = $this->client->get(
            "/payment/{$reference}",
            $bearerToken,
            false,
            $this->config->freemopay_status_check_timeout
        );

        Log::info("[FreeMoPay Service] Status check response: " . json_encode($response));

        return $response;
    }

    /**
     * Normalize phone number for FreeMoPay (237XXXXXXXXX or 243XXXXXXXXX)
     *
     * @param string $phone
     * @return string
     * @throws \Exception
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        if (!$phone) {
            throw new \Exception('Phone number is required');
        }

        // Remove +, spaces, dashes
        $cleaned = preg_replace('/[\s\-+]/', '', $phone);

        // Check if starts with 243 (RDC) or 237 (Cameroon)
        if (!str_starts_with($cleaned, '243') && !str_starts_with($cleaned, '237')) {
            throw new \Exception("Phone number must start with 243 (RDC) or 237 (Cameroon): {$phone}");
        }

        // Check length (12 digits expected)
        if (strlen($cleaned) !== 12 || !ctype_digit($cleaned)) {
            throw new \Exception("Invalid phone format. Expected 12 digits (237XXXXXXXXX or 243XXXXXXXXX): {$phone}");
        }

        return $cleaned;
    }

    /**
     * Generate a unique external ID
     *
     * @param string $prefix
     * @return string
     */
    protected function generateExternalId(string $prefix = 'PAY'): string
    {
        $timestamp = now()->format('YmdHis');
        return "{$prefix}-{$timestamp}";
    }

    /**
     * Ensure external ID is unique
     *
     * @param string $baseExternalId
     * @return string
     */
    protected function ensureUniqueExternalId(string $baseExternalId): string
    {
        $externalId = $baseExternalId;
        $counter = 1;

        while (Payment::where('external_id', $externalId)->exists()) {
            $externalId = "{$baseExternalId}-{$counter}";
            $counter++;
            Log::debug("[FreeMoPay Service] External ID exists, trying: {$externalId}");
        }

        return $externalId;
    }

    /**
     * Test FreeMoPay connection by generating a token
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            if (!$this->config || !$this->config->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'FreeMoPay configuration is incomplete',
                    'data' => null
                ];
            }

            // Try to get/generate a token
            $token = $this->tokenManager->getToken();

            return [
                'success' => true,
                'message' => 'FreeMoPay connection successful, token generated',
                'data' => [
                    'token_length' => strlen($token),
                    'token_preview' => substr($token, 0, 10) . '...'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection test failed: {$e->getMessage()}",
                'data' => null
            ];
        }
    }
}
