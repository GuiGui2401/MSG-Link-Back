<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentConfig;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DepositService
{
    private LigosAppService $ligosAppService;
    private CinetPayService $cinetPayService;

    public function __construct(
        LigosAppService $ligosAppService,
        CinetPayService $cinetPayService
    ) {
        $this->ligosAppService = $ligosAppService;
        $this->cinetPayService = $cinetPayService;
    }

    /**
     * Initier un dépôt selon le provider configuré
     */
    public function initiateDeposit(User $user, float $amount, ?string $phoneNumber = null): array
    {
        // Récupérer le provider configuré pour les dépôts
        $provider = PaymentConfig::getDepositProvider();

        Log::info('💰 [DEPOSIT] Initiation dépôt', [
            'user_id' => $user->id,
            'amount' => $amount,
            'provider' => $provider,
        ]);

        // Générer une référence unique
        $reference = 'DEPOSIT-' . time() . '-' . $user->id;

        // Créer l'enregistrement de paiement en attente
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => Payment::TYPE_DEPOSIT,
            'provider' => $provider,
            'amount' => $amount,
            'currency' => 'XAF',
            'status' => Payment::STATUS_PENDING,
            'reference' => $reference,
        ]);

        // Initier le paiement selon le provider
        try {
            if ($provider === PaymentConfig::PROVIDER_LIGOSAPP) {
                return $this->initiateLigosAppDeposit($user, $payment, $amount);
            } elseif ($provider === PaymentConfig::PROVIDER_CINETPAY) {
                return $this->initiateCinetPayDeposit($user, $payment, $amount, $phoneNumber);
            } else {
                throw new \Exception("Provider non supporté: {$provider}");
            }
        } catch (\Exception $e) {
            Log::error('❌ [DEPOSIT] Erreur initiation', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            // Marquer le paiement comme échoué
            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Initier un dépôt via LigosApp
     */
    private function initiateLigosAppDeposit(User $user, Payment $payment, float $amount): array
    {
        $result = $this->ligosAppService->initiatePayment([
            'reference' => $payment->reference,
            'amount' => $amount,
            'description' => 'Dépôt wallet - ' . config('app.name'),
            'customer_email' => $user->email,
            'customer_name' => $user->first_name . ' ' . $user->last_name,
        ]);

        if ($result['success']) {
            Log::info('✅ [DEPOSIT] LigosApp initié', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
            ]);

            return [
                'success' => true,
                'provider' => 'ligosapp',
                'payment_url' => $result['data']['checkout_url'] ?? null,
                'reference' => $payment->reference,
                'payment_id' => $payment->id,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Erreur LigosApp',
        ];
    }

    /**
     * Initier un dépôt via CinetPay
     */
    private function initiateCinetPayDeposit(User $user, Payment $payment, float $amount, ?string $phoneNumber): array
    {
        $result = $this->cinetPayService->initiatePayment([
            'transaction_id' => $payment->reference,
            'amount' => $amount,
            'description' => 'Dépôt wallet - ' . config('app.name'),
            'customer_id' => (string) $user->id,
            'customer_name' => $user->first_name ?? 'User',
            'customer_surname' => $user->last_name ?? 'Weylo',
            'customer_email' => $user->email,
            'customer_phone_number' => $phoneNumber ?? $user->phone ?? '+237600000000',
        ]);

        if ($result['success']) {
            Log::info('✅ [DEPOSIT] CinetPay initié', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
            ]);

            return [
                'success' => true,
                'provider' => 'cinetpay',
                'payment_url' => $result['data']['payment_url'] ?? null,
                'payment_token' => $result['data']['payment_token'] ?? null,
                'reference' => $payment->reference,
                'payment_id' => $payment->id,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Erreur CinetPay',
        ];
    }

    /**
     * Obtenir les informations du provider actif pour les dépôts
     */
    public function getActiveDepositProvider(): array
    {
        $provider = PaymentConfig::getDepositProvider();
        $providers = PaymentConfig::getAvailableProviders();

        return [
            'provider' => $provider,
            'info' => $providers[$provider] ?? null,
        ];
    }
}
