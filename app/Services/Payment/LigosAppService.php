<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LigosAppService implements PaymentServiceInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $notifyUrl;
    private string $returnUrl;

    public function __construct()
    {
        $this->apiKey = config('services.ligosapp.api_key');
        $this->baseUrl = config('services.ligosapp.base_url', 'https://api.lygosapp.com/v1');
        $this->notifyUrl = config('app.url') . '/api/v1/payments/webhook/ligosapp';
        $this->returnUrl = config('app.frontend_url') . '/payment/callback';
    }

    /**
     * Initier un paiement via LigosApp
     */
    public function initiatePayment(array $data): array
    {
        $orderId = $data['reference'] ?? 'LG-' . Str::upper(Str::random(12));

        $payload = [
            'amount' => (int) $data['amount'],
            'shop_name' => config('app.name', 'MSG Link'),
            'order_id' => $orderId,
            'message' => $data['description'] ?? 'Paiement MSG Link',
            'success_url' => $data['success_url'] ?? $this->returnUrl . '?status=success&reference=' . $orderId,
            'failure_url' => $data['failure_url'] ?? $this->returnUrl . '?status=failed&reference=' . $orderId,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/gateway", $payload);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['link'])) {
                    return [
                        'success' => true,
                        'reference' => $orderId,
                        'payment_url' => $result['link'],
                        'gateway_id' => $result['id'] ?? null,
                        'provider' => 'ligosapp',
                    ];
                }
            }

            Log::error('LigosApp payment initiation failed', [
                'response' => $response->json(),
                'status' => $response->status(),
                'payload' => array_diff_key($payload, ['api_key' => '']),
            ]);

            throw new \Exception($response->json()['message'] ?? 'Erreur lors de l\'initiation du paiement LigosApp');
        } catch (\Exception $e) {
            Log::error('LigosApp payment exception', [
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
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get("{$this->baseUrl}/gateway/payin/{$reference}");

            if ($response->successful()) {
                $result = $response->json();

                // Mapper le statut LigosApp vers notre format interne
                $status = $this->mapStatus($result['status'] ?? '');

                return [
                    'status' => $status,
                    'provider_reference' => $result['id'] ?? null,
                    'amount' => $result['amount'] ?? 0,
                    'metadata' => $result['metadata'] ?? [],
                    'raw' => $result,
                ];
            }

            throw new \Exception('Impossible de vérifier le statut du paiement');
        } catch (\Exception $e) {
            Log::error('LigosApp check status exception', [
                'reference' => $reference,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Traiter le webhook LigosApp
     */
    public function handleWebhook(array $payload): array
    {
        Log::info('LigosApp webhook received', $payload);

        $orderId = $payload['order_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$orderId) {
            return [
                'success' => false,
                'error' => 'Order ID manquant',
            ];
        }

        // Vérifier le statut réel via l'API
        try {
            $statusResult = $this->checkPaymentStatus($orderId);

            return [
                'success' => true,
                'reference' => $orderId,
                'status' => $statusResult['status'],
                'amount' => $statusResult['amount'],
                'metadata' => $statusResult['metadata'] ?? [],
            ];
        } catch (\Exception $e) {
            // Si la vérification échoue, utiliser les données du webhook
            return [
                'success' => true,
                'reference' => $orderId,
                'status' => $this->mapStatus($status),
                'amount' => $payload['amount'] ?? 0,
                'metadata' => [],
            ];
        }
    }

    /**
     * Initier un transfert Mobile Money (pour les retraits)
     * Note: LigosApp est principalement pour les paiements entrants.
     * Les transferts sortants peuvent nécessiter CinetPay ou InTouch.
     */
    public function initiateTransfer(array $data): array
    {
        // LigosApp ne supporte pas encore les transferts sortants
        // Utiliser CinetPay pour les retraits
        throw new \Exception('Les transferts sortants ne sont pas supportés par LigosApp. Utilisez CinetPay pour les retraits.');
    }

    /**
     * Mapper le statut LigosApp vers notre format interne
     */
    private function mapStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'success', 'successful', 'completed', 'paid' => 'completed',
            'failed', 'failure', 'declined', 'rejected' => 'failed',
            'cancelled', 'canceled' => 'failed',
            'pending', 'processing', 'initiated' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Récupérer les détails d'une gateway de paiement
     */
    public function getGateway(string $gatewayId): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get("{$this->baseUrl}/gateway/{$gatewayId}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Impossible de récupérer la gateway');
        } catch (\Exception $e) {
            Log::error('LigosApp get gateway exception', [
                'gateway_id' => $gatewayId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Lister toutes les gateways de paiement
     */
    public function listGateways(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get("{$this->baseUrl}/gateway");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Impossible de lister les gateways');
        } catch (\Exception $e) {
            Log::error('LigosApp list gateways exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
