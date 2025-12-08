<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CinetPayService implements PaymentServiceInterface
{
    private string $apiKey;
    private string $siteId;
    private string $secretKey;
    private string $baseUrl;
    private string $notifyUrl;
    private string $returnUrl;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->secretKey = config('services.cinetpay.secret_key');
        $this->baseUrl = config('services.cinetpay.base_url', 'https://api-checkout.cinetpay.com/v2');
        $this->notifyUrl = config('app.url') . '/api/v1/payments/webhook/cinetpay';
        $this->returnUrl = config('app.frontend_url') . '/payment/callback';
    }

    /**
     * Initier un paiement via CinetPay
     */
    public function initiatePayment(array $data): array
    {
        $transactionId = $data['reference'] ?? 'CP-' . Str::upper(Str::random(12));

        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'XAF',
            'description' => $data['description'] ?? 'Paiement MSG Link',
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl,
            'channels' => 'ALL',
            'metadata' => json_encode($data['metadata'] ?? []),
            'customer_name' => $data['user']->full_name ?? '',
            'customer_email' => $data['user']->email ?? '',
            'customer_phone_number' => $data['user']->phone ?? '',
            'customer_address' => 'Cameroun',
            'customer_city' => 'Douala',
            'customer_country' => 'CM',
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/payment", $payload);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['data']['payment_url'])) {
                    return [
                        'success' => true,
                        'reference' => $transactionId,
                        'payment_url' => $result['data']['payment_url'],
                        'payment_token' => $result['data']['payment_token'] ?? null,
                        'provider' => 'cinetpay',
                    ];
                }
            }

            Log::error('CinetPay payment initiation failed', [
                'response' => $response->json(),
                'payload' => array_diff_key($payload, ['apikey' => '']),
            ]);

            throw new \Exception($result['message'] ?? 'Erreur lors de l\'initiation du paiement');
        } catch (\Exception $e) {
            Log::error('CinetPay payment exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus(string $reference): array
    {
        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $reference,
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/payment/check", $payload);

            if ($response->successful()) {
                $result = $response->json();
                $data = $result['data'] ?? [];

                $status = match ($data['status'] ?? '') {
                    'ACCEPTED' => 'completed',
                    'REFUSED', 'CANCELLED' => 'failed',
                    default => 'pending',
                };

                return [
                    'status' => $status,
                    'provider_reference' => $data['payment_method'] ?? null,
                    'amount' => $data['amount'] ?? 0,
                    'metadata' => json_decode($data['metadata'] ?? '{}', true),
                    'raw' => $data,
                ];
            }

            throw new \Exception('Impossible de vérifier le statut du paiement');
        } catch (\Exception $e) {
            Log::error('CinetPay check status exception', [
                'reference' => $reference,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Traiter le webhook CinetPay
     */
    public function handleWebhook(array $payload): array
    {
        Log::info('CinetPay webhook received', $payload);

        $transactionId = $payload['cpm_trans_id'] ?? null;

        if (!$transactionId) {
            return [
                'success' => false,
                'error' => 'Transaction ID manquant',
            ];
        }

        // Vérifier le statut réel via l'API
        try {
            $statusResult = $this->checkPaymentStatus($transactionId);

            return [
                'success' => true,
                'reference' => $transactionId,
                'status' => $statusResult['status'],
                'amount' => $statusResult['amount'],
                'metadata' => $statusResult['metadata'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'reference' => $transactionId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initier un transfert Mobile Money (pour les retraits)
     */
    public function initiateTransfer(array $data): array
    {
        // CinetPay propose aussi des transferts sortants
        // Cette méthode doit être implémentée selon la documentation CinetPay

        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $data['reference'],
            'amount' => $data['amount'],
            'currency' => 'XAF',
            'phone' => $data['phone'],
            'phone_prefix' => '237',
            'payment_method' => $this->mapProvider($data['provider']),
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/transfer", $payload);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'reference' => $data['reference'],
                    'provider_reference' => $result['data']['transfer_id'] ?? null,
                    'status' => 'processing',
                ];
            }

            throw new \Exception($response->json()['message'] ?? 'Erreur lors du transfert');
        } catch (\Exception $e) {
            Log::error('CinetPay transfer exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mapper le provider vers le format CinetPay
     */
    private function mapProvider(string $provider): string
    {
        return match ($provider) {
            'mtn_momo' => 'MOMO',
            'orange_money' => 'OM',
            default => 'MOMO',
        };
    }

    /**
     * Vérifier la signature du webhook
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // Implémenter selon la documentation CinetPay
        $computedSignature = hash('sha256', $payload['cpm_trans_id'] . $this->secretKey);
        return hash_equals($computedSignature, $signature);
    }
}
