<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CinetPayService implements PaymentServiceInterface
{
    private string $apiKey;
    private string $siteId;
    private string $secretKey;
    private string $notifyUrl;
    private string $returnUrl;
    private string $baseUrl = 'https://api-checkout.cinetpay.com/v2';

    public function __construct()
    {
        $this->apiKey = Setting::get('cinetpay_api_key', config('cinetpay.api_key'));
        $this->siteId = Setting::get('cinetpay_site_id', config('cinetpay.site_id'));
        $this->secretKey = Setting::get('cinetpay_secret_key', config('cinetpay.secret_key'));
        $this->notifyUrl = Setting::get('cinetpay_notify_url', config('cinetpay.notify_url')) ?? config('app.url') . '/api/v1/payments/webhook/cinetpay';
        $this->returnUrl = config('app.frontend_url', 'http://localhost:3000') . '/payment/callback';
    }

    /**
     * Initier un paiement via CinetPay
     */
    public function initiatePayment(array $data): array
    {
        $transactionId = $data['transaction_id'] ?? $data['reference'] ?? 'CP-' . time() . rand(1000, 9999);

        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'] ?? 'XAF',
            'description' => $data['description'] ?? 'Paiement',
            'channels' => $data['channels'] ?? 'ALL',
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl,
            'customer_id' => $data['customer_id'] ?? '',
            'customer_name' => $data['customer_name'] ?? 'User',
            'customer_surname' => $data['customer_surname'] ?? 'Weylo',
            'customer_email' => $data['customer_email'] ?? '',
            'customer_phone_number' => $data['customer_phone_number'] ?? '+237600000000',
            'customer_address' => $data['customer_address'] ?? 'Douala',
            'customer_city' => $data['customer_city'] ?? 'Douala',
            'customer_country' => $data['customer_country'] ?? 'CM',
            'customer_state' => $data['customer_state'] ?? 'CM',
            'customer_zip_code' => $data['customer_zip_code'] ?? '00237',
            'metadata' => $data['metadata'] ?? '',
            'lang' => $data['lang'] ?? 'fr'
        ];

        Log::info('🔵 [CINETPAY] Initiation paiement', [
            'transaction_id' => $transactionId,
            'amount' => $payload['amount'],
            'return_url' => $this->returnUrl,
            'notify_url' => $this->notifyUrl,
            'full_payload' => $payload,
        ]);

        try {
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'Weylo-App/1.0'
            ])->post("{$this->baseUrl}/payment", $payload);

            if ($response->failed()) {
                Log::error('❌ [CINETPAY] Erreur HTTP', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Erreur de connexion à CinetPay'
                ];
            }

            $responseData = $response->json();

            Log::info('📥 [CINETPAY] Réponse reçue', [
                'code' => $responseData['code'] ?? null,
                'message' => $responseData['message'] ?? null,
            ]);

            if (!isset($responseData['code']) || $responseData['code'] !== '201') {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                    'data' => $responseData
                ];
            }

            return [
                'success' => true,
                'reference' => $transactionId,
                'data' => [
                    'payment_url' => $responseData['data']['payment_url'] ?? null,
                    'payment_token' => $responseData['data']['payment_token'] ?? null,
                ],
                'provider' => 'cinetpay',
            ];

        } catch (\Exception $e) {
            Log::error('❌ [CINETPAY] Exception', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus(string $reference): array
    {
        Log::info('🔍 [CINETPAY] Vérification statut', ['reference' => $reference]);

        try {
            $payload = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $reference
            ];

            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'Weylo-App/1.0'
            ])->post("{$this->baseUrl}/payment/check", $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === '00') {
                $status = $responseData['data']['status'] ?? 'PENDING';

                // Mapper les statuts CinetPay vers nos statuts
                $mappedStatus = match($status) {
                    'ACCEPTED' => 'completed',
                    'REFUSED' => 'failed',
                    'CANCELLED', 'CANCELED' => 'cancelled',
                    default => 'pending',
                };

                return [
                    'status' => $mappedStatus,
                    'amount' => $responseData['data']['amount'] ?? 0,
                    'provider_reference' => $reference,
                    'metadata' => $responseData['data'] ?? [],
                ];
            }

            return [
                'status' => 'pending',
                'amount' => 0,
                'provider_reference' => $reference,
                'metadata' => [],
            ];

        } catch (\Exception $e) {
            Log::error('❌ [CINETPAY] Erreur vérification', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'pending',
                'amount' => 0,
                'provider_reference' => $reference,
                'metadata' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Traiter le webhook CinetPay
     */
    public function handleWebhook(array $payload): array
    {
        Log::info('🔔 [CINETPAY] Traitement webhook', [
            'transaction_id' => $payload['cpm_trans_id'] ?? null,
        ]);

        $transactionId = $payload['cpm_trans_id'] ?? null;

        if (!$transactionId) {
            Log::error('❌ [CINETPAY] Transaction ID manquant dans le webhook');
            return [
                'success' => false,
                'message' => 'Transaction ID manquant',
            ];
        }

        // Vérifier le statut via l'API
        $status = $this->checkPaymentStatus($transactionId);

        return [
            'success' => true,
            'reference' => $transactionId,
            'status' => $status['status'],
            'amount' => $status['amount'],
        ];
    }

    /**
     * Effectuer un transfert (pour les retraits)
     */
    public function initiateTransfer(array $data): array
    {
        Log::info('💸 [CINETPAY] Initiation transfert', [
            'amount' => $data['amount'],
            'phone' => $data['phone'] ?? null,
        ]);

        try {
            $payload = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transfer_password' => Setting::get('cinetpay_transfer_password', config('cinetpay.transfer_password')),
                'phone' => $data['phone'],
                'amount' => (int) $data['amount'],
                'transaction_id' => $data['reference'] ?? 'TF-' . time() . rand(1000, 9999),
                'provider' => strtoupper($data['provider'] ?? 'ORANGE'),
            ];

            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'Weylo-App/1.0'
            ])->post("{$this->baseUrl}/transfer", $payload);

            $responseData = $response->json();

            Log::info('📥 [CINETPAY] Réponse transfert', $responseData);

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === '00') {
                return [
                    'success' => true,
                    'reference' => $payload['transaction_id'],
                    'message' => 'Transfert initié avec succès',
                    'data' => $responseData['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Erreur lors du transfert',
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('❌ [CINETPAY] Erreur transfert', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique: ' . $e->getMessage(),
            ];
        }
    }
}
