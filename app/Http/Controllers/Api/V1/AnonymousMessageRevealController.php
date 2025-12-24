<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnonymousMessage;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\LygosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnonymousMessageRevealController extends Controller
{
    public function __construct(
        private LygosService $lygosService
    ) {}

    /**
     * Initier le paiement pour révéler l'identité d'un message anonyme
     */
    public function initiatePayment(Request $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est le destinataire du message
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas le destinataire de ce message.',
            ], 403);
        }

        // Vérifier que l'identité n'est pas déjà révélée
        if ($message->is_identity_revealed) {
            return response()->json([
                'success' => false,
                'message' => 'L\'identité de ce message a déjà été révélée.',
            ], 400);
        }

        // Récupérer le prix depuis les settings
        $price = Setting::get('reveal_anonymous_price', 1000);

        // Valider les données de paiement
        $request->validate([
            'phone_number' => 'required|string|regex:/^237[0-9]{9}$/',
            'operator' => 'required|string|in:MTN_MOMO_CMR,ORANGE_MONEY_CMR',
        ]);

        try {
            DB::beginTransaction();

            // Créer une référence unique
            $reference = 'REVEAL-' . strtoupper(Str::random(12));

            // Créer l'enregistrement de paiement
            $payment = Payment::create([
                'user_id' => $user->id,
                'type' => 'reveal_identity',
                'provider' => 'ligosapp',
                'amount' => $price,
                'currency' => 'XAF',
                'status' => 'pending',
                'reference' => $reference,
                'metadata' => [
                    'message_id' => $message->id,
                    'phone_number' => $request->phone_number,
                    'operator' => $request->operator,
                ],
            ]);

            // Initialiser le paiement avec Lygos
            $lygosResponse = $this->lygosService->initializePayment(
                trackId: $reference,
                amount: $price,
                phoneNumber: $request->phone_number,
                operator: $request->operator,
                country: 'CMR',
                currency: 'XAF'
            );

            // Mettre à jour le payment avec les infos Lygos
            $payment->update([
                'provider_reference' => $lygosResponse['order_id'],
                'status' => 'processing',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'lygos_payment_id' => $lygosResponse['id'] ?? null,
                    'lygos_link' => $lygosResponse['link'] ?? null,
                ]),
            ]);

            DB::commit();

            Log::info('✅ [REVEAL] Paiement initié', [
                'user_id' => $user->id,
                'message_id' => $message->id,
                'reference' => $reference,
                'order_id' => $lygosResponse['order_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié avec succès.',
                'data' => [
                    'payment_id' => $payment->id,
                    'reference' => $reference,
                    'order_id' => $lygosResponse['order_id'],
                    'amount' => $price,
                    'currency' => 'XAF',
                    'payment_link' => $lygosResponse['link'] ?? null,
                    'status' => 'processing',
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ [REVEAL] Erreur lors de l\'initiation du paiement', [
                'user_id' => $user->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut du paiement et révéler l'identité si payé
     */
    public function checkPaymentStatus(Request $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est le destinataire du message
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas le destinataire de ce message.',
            ], 403);
        }

        // Vérifier que l'identité n'est pas déjà révélée
        if ($message->is_identity_revealed) {
            return response()->json([
                'success' => true,
                'message' => 'L\'identité a déjà été révélée.',
                'data' => [
                    'status' => 'revealed',
                    'sender' => [
                        'id' => $message->sender->id,
                        'username' => $message->sender->username,
                        'full_name' => $message->sender->full_name,
                        'avatar_url' => $message->sender->avatar_url,
                    ],
                ],
            ]);
        }

        // Récupérer le paiement en cours pour ce message
        $payment = Payment::where('user_id', $user->id)
            ->where('type', 'reveal_identity')
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->filter(function ($p) use ($message) {
                return isset($p->metadata['message_id']) && $p->metadata['message_id'] == $message->id;
            })
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun paiement en cours trouvé pour ce message.',
            ], 404);
        }

        try {
            // Vérifier le statut auprès de Lygos
            $lygosStatus = $this->lygosService->getTransactionStatus($payment->provider_reference);

            Log::info('🔍 [REVEAL] Vérification du statut - DÉTAILS COMPLETS', [
                'payment_id' => $payment->id,
                'order_id' => $payment->provider_reference,
                'lygos_full_response' => $lygosStatus,
                'lygos_status' => $lygosStatus['status'] ?? 'unknown',
                'lygos_status_lowercase' => isset($lygosStatus['status']) ? strtolower($lygosStatus['status']) : 'unknown',
            ]);

            // Si le paiement est réussi (selon la doc Lygos: uniquement "success")
            // Référence: https://github.com/Warano02/lygos - les statuts sont: pending, success, failed
            if (isset($lygosStatus['status']) && strtolower($lygosStatus['status']) === 'success') {
                DB::beginTransaction();

                // Mettre à jour le paiement
                $payment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Révéler l'identité du message
                $message->revealIdentity();

                DB::commit();

                Log::info('✅ [REVEAL] Identité révélée', [
                    'payment_id' => $payment->id,
                    'message_id' => $message->id,
                    'sender_id' => $message->sender_id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement confirmé. Identité révélée.',
                    'data' => [
                        'status' => 'revealed',
                        'sender' => [
                            'id' => $message->sender->id,
                            'username' => $message->sender->username,
                            'full_name' => $message->sender->full_name,
                            'avatar_url' => $message->sender->avatar_url,
                        ],
                    ],
                ]);
            }

            // Si le paiement a échoué
            if (isset($lygosStatus['status']) && in_array(strtolower($lygosStatus['status']), ['failed', 'cancelled', 'expired'])) {
                $payment->update([
                    'status' => 'failed',
                    'failure_reason' => 'Transaction ' . $lygosStatus['status'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Le paiement a échoué.',
                    'data' => [
                        'status' => 'failed',
                        'reason' => $lygosStatus['status'],
                    ],
                ], 400);
            }

            // Vérifier si c'est un statut non officiel
            $currentStatus = strtolower($lygosStatus['status'] ?? 'unknown');
            $officialStatuses = ['success', 'failed', 'pending'];

            if (!in_array($currentStatus, $officialStatuses) && $currentStatus !== 'unknown') {
                Log::warning('⚠️ [REVEAL] STATUT LYGOS NON OFFICIEL DÉTECTÉ!', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->provider_reference,
                    'unofficial_status' => $lygosStatus['status'],
                    'message' => 'Ce statut ne fait pas partie de la documentation officielle Lygos!',
                    'official_statuses' => $officialStatuses,
                    'action' => 'Paiement considéré comme en attente par sécurité',
                    'documentation' => 'https://github.com/Warano02/lygos',
                ]);
            }

            // Paiement toujours en attente
            Log::info('⏳ [REVEAL] Paiement toujours en attente', [
                'payment_id' => $payment->id,
                'current_status' => $lygosStatus['status'] ?? 'unknown',
                'all_data' => $lygosStatus,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement en cours de traitement.',
                'data' => [
                    'status' => 'processing',
                    'payment_link' => $payment->metadata['lygos_link'] ?? null,
                    'lygos_status' => $lygosStatus['status'] ?? null, // Pour debug
                ],
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Si la transaction n'est pas trouvée, c'est probablement qu'elle n'a pas encore été créée
            if (str_contains($errorMessage, 'Transaction not found') || str_contains($errorMessage, 'TRANSACTION_NOT_FOUND')) {
                Log::warning('⚠️ [REVEAL] Transaction pas encore créée sur Lygos', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->provider_reference,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement en attente. Veuillez compléter le paiement sur votre téléphone.',
                    'data' => [
                        'status' => 'processing',
                        'payment_link' => $payment->metadata['lygos_link'] ?? null,
                    ],
                ]);
            }

            Log::error('❌ [REVEAL] Erreur lors de la vérification du statut', [
                'payment_id' => $payment->id,
                'error' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du paiement',
            ], 500);
        }
    }

    /**
     * Obtenir le prix pour révéler une identité
     */
    public function getRevealPrice(Request $request): JsonResponse
    {
        $price = Setting::get('reveal_anonymous_price', 1000);

        return response()->json([
            'success' => true,
            'data' => [
                'price' => $price,
                'currency' => 'XAF',
                'formatted_price' => number_format($price, 0, ',', ' ') . ' FCFA',
            ],
        ]);
    }
}
