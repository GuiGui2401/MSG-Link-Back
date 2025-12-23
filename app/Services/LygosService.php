<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LygosService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $shopName;

    public function __construct()
    {
        $this->apiKey = config('services.ligosapp.api_key');
        $this->baseUrl = config('services.ligosapp.base_url');
        $this->shopName = config('app.name', 'MSG-Link');
    }

    /**
     * Initier un paiement via Lygos
     *
     * @param string $trackId Notre référence unique
     * @param int $amount Montant en FCFA
     * @param string $phoneNumber Numéro de téléphone
     * @param string $operator Opérateur (MTN_MOMO_CMR, ORANGE_MONEY_CMR, etc.)
     * @param string $country Code pays (CMR par défaut)
     * @param string $currency Devise (XAF par défaut)
     * @return array
     * @throws \Exception
     */
    public function initializePayment(
        string $trackId,
        int $amount,
        string $phoneNumber,
        string $operator = 'MTN_MOMO_CMR',
        string $country = 'CMR',
        string $currency = 'XAF'
    ): array {
        try {
            Log::info('🚀 [LYGOS] Initialisation du paiement', [
                'track_id' => $trackId,
                'amount' => $amount,
                'phone_number' => $phoneNumber,
                'operator' => $operator,
            ]);

            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/gateway", [
                'track_id' => $trackId,
                'shop_name' => $this->shopName,
                'amount' => $amount,
                'phone_number' => $phoneNumber,
                'country' => $country,
                'currency' => $currency,
                'operator' => $operator,
            ]);

            if ($response->failed()) {
                Log::error('❌ [LYGOS] Échec initialisation paiement', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                throw new \Exception('Échec de l\'initialisation du paiement Lygos: ' . $response->body());
            }

            $data = $response->json();

            Log::info('✅ [LYGOS] Paiement initialisé avec succès', [
                'order_id' => $data['order_id'] ?? null,
                'link' => $data['link'] ?? null,
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('❌ [LYGOS] Exception lors de l\'initialisation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Vérifier le statut d'une transaction
     *
     * @param string $orderId ID de la commande Lygos
     * @return array
     * @throws \Exception
     */
    public function getTransactionStatus(string $orderId): array
    {
        try {
            Log::info('🔍 [LYGOS] Vérification du statut de la transaction', [
                'order_id' => $orderId,
            ]);

            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/gateway/payin/{$orderId}");

            if ($response->failed()) {
                Log::error('❌ [LYGOS] Échec vérification statut', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                throw new \Exception('Échec de la vérification du statut Lygos: ' . $response->body());
            }

            $data = $response->json();

            Log::info('✅ [LYGOS] Statut récupéré', [
                'order_id' => $data['order_id'] ?? null,
                'status' => $data['status'] ?? null,
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('❌ [LYGOS] Exception lors de la vérification', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Vérifier si une transaction est complétée
     *
     * @param string $orderId
     * @return bool
     */
    public function isTransactionCompleted(string $orderId): bool
    {
        try {
            $status = $this->getTransactionStatus($orderId);
            return isset($status['status']) && strtolower($status['status']) === 'success';
        } catch (\Exception $e) {
            return false;
        }
    }
}
