<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\GiftTransaction;
use App\Models\PremiumSubscription;
use App\Services\Payment\CinetPayService;
use App\Services\Payment\LigosAppService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private CinetPayService $cinetPayService,
        private LigosAppService $ligosAppService,
        private NotificationService $notificationService
    ) {}

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkStatus(Request $request, string $reference): JsonResponse
    {
        try {
            $status = $this->cinetPayService->checkPaymentStatus($reference);

            return response()->json([
                'reference' => $reference,
                'status' => $status['status'],
                'amount' => $status['amount'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la vérification du statut.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook CinetPay
     */
    public function webhookCinetPay(Request $request): JsonResponse
    {
        // CinetPay ping l'URL avec GET pour tester la disponibilité
        if ($request->isMethod('get')) {
            Log::info('🔔 [CINETPAY WEBHOOK] Ping GET reçu de CinetPay (test de disponibilité)');
            return response()->json(['status' => 'ok'], 200);
        }

        Log::info('🔔 [CINETPAY WEBHOOK] ==================== NOUVELLE NOTIFICATION ====================');
        Log::info('🔔 [CINETPAY WEBHOOK] Headers', $request->headers->all());
        Log::info('🔔 [CINETPAY WEBHOOK] Body', $request->all());

        // Vérification HMAC pour la sécurité
        $receivedToken = $request->header('x-token');

        if (!$receivedToken) {
            Log::error('❌ [CINETPAY WEBHOOK] Token HMAC manquant dans l\'entête');
            return response()->json(['status' => 'error', 'message' => 'Token manquant'], 401);
        }

        // Récupérer les données de la notification
        $cpm_site_id = $request->input('cpm_site_id');
        $cpm_trans_id = $request->input('cpm_trans_id');
        $cpm_trans_date = $request->input('cpm_trans_date', '');
        $cpm_amount = $request->input('cpm_amount', '');
        $cpm_currency = $request->input('cpm_currency', '');
        $signature = $request->input('signature', '');
        $payment_method = $request->input('payment_method', '');
        $cel_phone_num = $request->input('cel_phone_num', '');
        $cpm_phone_prefixe = $request->input('cpm_phone_prefixe', '');
        $cpm_language = $request->input('cpm_language', '');
        $cpm_version = $request->input('cpm_version', '');
        $cpm_payment_config = $request->input('cpm_payment_config', '');
        $cpm_page_action = $request->input('cpm_page_action', '');
        $cpm_custom = $request->input('cpm_custom', '');
        $cpm_designation = $request->input('cpm_designation', '');
        $cpm_error_message = $request->input('cpm_error_message', '');

        // Vérifier les données obligatoires
        if (!$cpm_trans_id || !$cpm_site_id) {
            Log::error('❌ [CINETPAY WEBHOOK] Données obligatoires manquantes', [
                'trans_id' => $cpm_trans_id,
                'site_id' => $cpm_site_id
            ]);
            return response()->json(['status' => 'error', 'message' => 'Données manquantes'], 400);
        }

        // Construire la chaîne pour le token HMAC selon la doc CinetPay
        $data = $cpm_site_id . $cpm_trans_id . $cpm_trans_date . $cpm_amount . $cpm_currency .
                $signature . $payment_method . $cel_phone_num . $cpm_phone_prefixe .
                $cpm_language . $cpm_version . $cpm_payment_config . $cpm_page_action .
                $cpm_custom . $cpm_designation . $cpm_error_message;

        // Récupérer la clé secrète
        $secretKey = config('services.cinetpay.secret_key');

        if (empty($secretKey)) {
            Log::error('❌ [CINETPAY WEBHOOK] CINETPAY_SECRET_KEY non configurée');
            // En développement, on peut continuer, mais en production c'est critique
            // return response()->json(['status' => 'error', 'message' => 'Configuration manquante'], 500);
        } else {
            // Générer le token HMAC avec SHA256
            $generatedToken = hash_hmac('SHA256', $data, $secretKey);

            Log::info('🔐 [CINETPAY WEBHOOK] Vérification token HMAC', [
                'received_token' => substr($receivedToken, 0, 20) . '...',
                'generated_token' => substr($generatedToken, 0, 20) . '...',
                'tokens_match' => hash_equals($receivedToken, $generatedToken)
            ]);

            // Vérifier que les tokens correspondent
            if (!hash_equals($receivedToken, $generatedToken)) {
                Log::error('❌ [CINETPAY WEBHOOK] Token HMAC invalide - Notification rejetée');
                return response()->json(['status' => 'error', 'message' => 'Token invalide'], 401);
            }

            Log::info('✅ [CINETPAY WEBHOOK] Token HMAC validé');
        }

        try {
            Log::info('🔵 [CINETPAY WEBHOOK] Traitement de la notification', [
                'transaction_id' => $cpm_trans_id,
                'amount' => $cpm_amount,
                'payment_method' => $payment_method,
            ]);

            $result = $this->cinetPayService->handleWebhook($request->all());

            if (!$result['success']) {
                Log::warning('⚠️ [CINETPAY WEBHOOK] Traitement échoué', $result);
                return response()->json(['status' => 'error'], 400);
            }

            $reference = $result['reference'];
            $status = $result['status'];

            Log::info('🔵 [CINETPAY WEBHOOK] Statut du paiement', [
                'reference' => $reference,
                'status' => $status,
            ]);

            // Traiter selon le type de transaction
            if (str_starts_with($reference, 'DEPOSIT-')) {
                Log::info('💰 [CINETPAY WEBHOOK] Traitement dépôt wallet');
                $this->processDepositPayment($reference, $status, Payment::PROVIDER_CINETPAY);
            } elseif (str_starts_with($reference, 'GIFT-')) {
                Log::info('🎁 [CINETPAY WEBHOOK] Traitement paiement cadeau');
                $this->processGiftPayment($reference, $status, Payment::PROVIDER_CINETPAY);
            } elseif (str_starts_with($reference, 'PREM-')) {
                Log::info('⭐ [CINETPAY WEBHOOK] Traitement abonnement premium');
                $this->processPremiumPayment($reference, $status, Payment::PROVIDER_CINETPAY);
            }

            Log::info('✅ [CINETPAY WEBHOOK] ==================== NOTIFICATION TRAITÉE ====================');
            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('❌ [CINETPAY WEBHOOK] Exception lors du traitement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Webhook LigosApp
     */
    public function webhookLigosApp(Request $request): JsonResponse
    {
        Log::info('LigosApp webhook received', $request->all());

        try {
            $result = $this->ligosAppService->handleWebhook($request->all());

            if (!$result['success']) {
                return response()->json(['status' => 'error'], 400);
            }

            $reference = $result['reference'];
            $status = $result['status'];

            // Traiter selon le type de transaction (même logique que CinetPay)
            if (str_starts_with($reference, 'DEPOSIT-')) {
                $this->processDepositPayment($reference, $status, Payment::PROVIDER_LIGOSAPP);
            } elseif (str_starts_with($reference, 'GIFT-')) {
                $this->processGiftPayment($reference, $status, Payment::PROVIDER_LIGOSAPP);
            } elseif (str_starts_with($reference, 'PREM-')) {
                $this->processPremiumPayment($reference, $status, Payment::PROVIDER_LIGOSAPP);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('LigosApp webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Webhook Intouch
     */
    public function webhookIntouch(Request $request): JsonResponse
    {
        Log::info('Intouch webhook received', $request->all());

        // TODO: Implémenter selon la documentation Intouch

        return response()->json(['status' => 'ok']);
    }

    /**
     * Traiter un paiement de cadeau
     */
    private function processGiftPayment(string $reference, string $status, string $provider = Payment::PROVIDER_CINETPAY): void
    {
        $transactionId = (int) str_replace('GIFT-', '', $reference);
        $transaction = GiftTransaction::find($transactionId);

        if (!$transaction) {
            Log::warning('Gift transaction not found', ['reference' => $reference]);
            return;
        }

        if ($transaction->status !== GiftTransaction::STATUS_PENDING) {
            Log::info('Gift transaction already processed', ['reference' => $reference]);
            return;
        }

        DB::transaction(function () use ($transaction, $status) {
            if ($status === 'completed') {
                $transaction->markAsCompleted();
                
                // Créer le message dans la conversation
                if ($transaction->conversation_id) {
                    \App\Models\ChatMessage::createGiftMessage(
                        $transaction->conversation,
                        $transaction->sender,
                        $transaction,
                        $transaction->message
                    );
                    
                    $transaction->conversation->updateAfterMessage();
                }

                // Envoyer notification
                $this->notificationService->sendGiftNotification($transaction);

                Log::info('Gift payment completed', ['transaction_id' => $transaction->id]);
            } else {
                $transaction->markAsFailed();
                Log::info('Gift payment failed', ['transaction_id' => $transaction->id]);
            }
        });
    }

    /**
     * Traiter un paiement d'abonnement premium
     */
    private function processPremiumPayment(string $reference, string $status, string $provider = Payment::PROVIDER_CINETPAY): void
    {
        $subscriptionId = (int) str_replace('PREM-', '', $reference);
        $subscription = PremiumSubscription::find($subscriptionId);

        if (!$subscription) {
            Log::warning('Premium subscription not found', ['reference' => $reference]);
            return;
        }

        if ($subscription->status !== PremiumSubscription::STATUS_PENDING) {
            Log::info('Premium subscription already processed', ['reference' => $reference]);
            return;
        }

        DB::transaction(function () use ($subscription, $status, $provider) {
            if ($status === 'completed') {
                $subscription->activate();

                // Créer l'enregistrement de paiement
                Payment::create([
                    'user_id' => $subscription->subscriber_id,
                    'type' => Payment::TYPE_SUBSCRIPTION,
                    'provider' => $provider,
                    'amount' => $subscription->amount,
                    'currency' => 'XAF',
                    'status' => Payment::STATUS_COMPLETED,
                    'reference' => $subscription->payment_reference,
                    'completed_at' => now(),
                ]);

                Log::info('Premium subscription activated', ['subscription_id' => $subscription->id, 'provider' => $provider]);
            } else {
                $subscription->delete();
                Log::info('Premium payment failed, subscription deleted', ['subscription_id' => $subscription->id]);
            }
        });
    }

    /**
     * Traiter un paiement de dépôt wallet
     */
    private function processDepositPayment(string $reference, string $status, string $provider = Payment::PROVIDER_CINETPAY): void
    {
        Log::info('💰 [DEPOSIT] Traitement du paiement de dépôt', [
            'reference' => $reference,
            'status' => $status,
            'provider' => $provider,
        ]);

        // Récupérer le paiement par référence
        $payment = Payment::where('reference', $reference)
            ->where('type', Payment::TYPE_DEPOSIT)
            ->first();

        if (!$payment) {
            Log::warning('⚠️ [DEPOSIT] Paiement introuvable', ['reference' => $reference]);
            return;
        }

        Log::info('💰 [DEPOSIT] Paiement trouvé', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
            'current_status' => $payment->status,
        ]);

        if ($payment->status === Payment::STATUS_COMPLETED) {
            Log::info('⚠️ [DEPOSIT] Paiement déjà traité', [
                'reference' => $reference,
                'payment_id' => $payment->id,
            ]);
            return;
        }

        DB::transaction(function () use ($payment, $status, $provider) {
            if ($status === 'completed') {
                Log::info('💰 [DEPOSIT] Marquage du paiement comme complété', [
                    'payment_id' => $payment->id,
                ]);

                // Marquer le paiement comme complété
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'provider' => $provider,
                    'completed_at' => now(),
                ]);

                // Créditer le wallet de l'utilisateur
                $user = $payment->user;
                $balanceBefore = $user->wallet_balance;

                Log::info('💰 [DEPOSIT] Crédit du wallet', [
                    'user_id' => $user->id,
                    'amount' => $payment->amount,
                    'balance_before' => $balanceBefore,
                ]);

                $user->creditWallet(
                    $payment->amount,
                    "Dépôt sur le wallet - {$payment->reference}",
                    $payment
                );

                $balanceAfter = $user->fresh()->wallet_balance;

                Log::info('✅ [DEPOSIT] Paiement complété et wallet crédité', [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'amount' => $payment->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'provider' => $provider,
                ]);
            } else {
                // Marquer le paiement comme échoué
                $payment->markAsFailed('Paiement refusé par ' . $provider);
                Log::info('❌ [DEPOSIT] Paiement échoué', [
                    'payment_id' => $payment->id,
                    'provider' => $provider,
                    'status' => $status,
                ]);
            }
        });
    }
}
