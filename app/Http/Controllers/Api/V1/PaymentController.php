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
        Log::info('CinetPay webhook received', $request->all());

        try {
            $result = $this->cinetPayService->handleWebhook($request->all());

            if (!$result['success']) {
                return response()->json(['status' => 'error'], 400);
            }

            $reference = $result['reference'];
            $status = $result['status'];

            // Traiter selon le type de transaction
            if (str_starts_with($reference, 'GIFT-')) {
                $this->processGiftPayment($reference, $status, Payment::PROVIDER_CINETPAY);
            } elseif (str_starts_with($reference, 'PREM-')) {
                $this->processPremiumPayment($reference, $status, Payment::PROVIDER_CINETPAY);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('CinetPay webhook error', [
                'error' => $e->getMessage(),
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
            if (str_starts_with($reference, 'GIFT-')) {
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
}
