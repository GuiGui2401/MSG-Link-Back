<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PremiumSubscriptionResource;
use App\Models\PremiumSubscription;
use App\Models\AnonymousMessage;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\Story;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PremiumController extends Controller
{
    public function __construct(
        private PaymentServiceInterface $paymentService
    ) {}

    /**
     * Liste des abonnements de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = PremiumSubscription::bySubscriber($user->id)
            ->with([
                'targetUser:id,first_name,last_name,username,avatar',
                'conversation',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'subscriptions' => PremiumSubscriptionResource::collection($subscriptions),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
            'stats' => [
                'active_count' => PremiumSubscription::bySubscriber($user->id)->active()->count(),
                'total_spent' => PremiumSubscription::bySubscriber($user->id)
                    ->whereIn('status', ['active', 'expired'])
                    ->sum('amount'),
            ],
        ]);
    }

    /**
     * Abonnements actifs
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = PremiumSubscription::bySubscriber($user->id)
            ->active()
            ->with([
                'targetUser:id,first_name,last_name,username,avatar',
                'conversation',
            ])
            ->orderBy('expires_at', 'asc')
            ->get();

        return response()->json([
            'subscriptions' => PremiumSubscriptionResource::collection($subscriptions),
        ]);
    }

    /**
     * S'abonner pour révéler l'identité d'un message
     */
    public function subscribeToMessage(Request $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();

        // Vérifications
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if ($message->is_identity_revealed) {
            return response()->json([
                'message' => 'L\'identité a déjà été révélée.',
            ], 422);
        }

        // Vérifier si un abonnement actif existe déjà
        if (PremiumSubscription::hasActiveForMessage($user->id, $message->id)) {
            return response()->json([
                'message' => 'Vous avez déjà un abonnement actif pour ce message.',
            ], 422);
        }

        // Créer l'abonnement en attente
        $subscription = PremiumSubscription::create([
            'subscriber_id' => $user->id,
            'target_user_id' => $message->sender_id,
            'type' => PremiumSubscription::TYPE_MESSAGE,
            'message_id' => $message->id,
            'amount' => PremiumSubscription::MONTHLY_PRICE,
            'status' => PremiumSubscription::STATUS_PENDING,
        ]);

        // Initier le paiement
        try {
            $paymentResult = $this->paymentService->initiatePayment([
                'amount' => PremiumSubscription::MONTHLY_PRICE,
                'currency' => 'XAF',
                'description' => 'Abonnement Premium - Révélation identité',
                'reference' => "PREM-{$subscription->id}",
                'user' => $user,
                'metadata' => [
                    'type' => 'subscription',
                    'subscription_id' => $subscription->id,
                ],
            ]);

            $subscription->update(['payment_reference' => $paymentResult['reference']]);

            return response()->json([
                'message' => 'Paiement initié.',
                'subscription_id' => $subscription->id,
                'price' => PremiumSubscription::MONTHLY_PRICE,
                'payment' => $paymentResult,
            ]);
        } catch (\Exception $e) {
            $subscription->delete();

            return response()->json([
                'message' => 'Erreur lors de l\'initiation du paiement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * S'abonner pour révéler les identités dans une conversation
     */
    public function subscribeToConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Vérifications
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Vérifier si un abonnement actif existe déjà
        if (PremiumSubscription::hasActiveForConversation($user->id, $conversation->id)) {
            return response()->json([
                'message' => 'Vous avez déjà un abonnement actif pour cette conversation.',
            ], 422);
        }

        $otherUser = $conversation->getOtherParticipant($user);

        // Créer l'abonnement en attente
        $subscription = PremiumSubscription::create([
            'subscriber_id' => $user->id,
            'target_user_id' => $otherUser->id,
            'type' => PremiumSubscription::TYPE_CONVERSATION,
            'conversation_id' => $conversation->id,
            'amount' => PremiumSubscription::MONTHLY_PRICE,
            'status' => PremiumSubscription::STATUS_PENDING,
        ]);

        // Initier le paiement
        try {
            $paymentResult = $this->paymentService->initiatePayment([
                'amount' => PremiumSubscription::MONTHLY_PRICE,
                'currency' => 'XAF',
                'description' => 'Abonnement Premium - Conversation',
                'reference' => "PREM-{$subscription->id}",
                'user' => $user,
                'metadata' => [
                    'type' => 'subscription',
                    'subscription_id' => $subscription->id,
                ],
            ]);

            $subscription->update(['payment_reference' => $paymentResult['reference']]);

            return response()->json([
                'message' => 'Paiement initié.',
                'subscription_id' => $subscription->id,
                'price' => PremiumSubscription::MONTHLY_PRICE,
                'payment' => $paymentResult,
            ]);
        } catch (\Exception $e) {
            $subscription->delete();

            return response()->json([
                'message' => 'Erreur lors de l\'initiation du paiement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmer un abonnement (après paiement réussi)
     */
    public function confirm(Request $request, PremiumSubscription $subscription): JsonResponse
    {
        if ($subscription->status !== PremiumSubscription::STATUS_PENDING) {
            return response()->json([
                'message' => 'Abonnement déjà traité.',
            ], 422);
        }

        DB::transaction(function () use ($subscription) {
            // Activer l'abonnement
            $subscription->activate();

            // Créer l'enregistrement de paiement
            Payment::create([
                'user_id' => $subscription->subscriber_id,
                'type' => Payment::TYPE_SUBSCRIPTION,
                'provider' => Payment::PROVIDER_LIGOSAPP,
                'amount' => $subscription->amount,
                'currency' => 'XAF',
                'status' => Payment::STATUS_COMPLETED,
                'reference' => $subscription->payment_reference,
                'completed_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Abonnement activé avec succès.',
            'subscription' => new PremiumSubscriptionResource($subscription->fresh()),
        ]);
    }

    /**
     * Annuler un abonnement (désactive le renouvellement automatique)
     */
    public function cancel(Request $request, PremiumSubscription $subscription): JsonResponse
    {
        $user = $request->user();

        if ($subscription->subscriber_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if (!$subscription->is_active) {
            return response()->json([
                'message' => 'Cet abonnement n\'est pas actif.',
            ], 422);
        }

        $subscription->cancel();

        return response()->json([
            'message' => 'Abonnement annulé. Il restera actif jusqu\'à la date d\'expiration.',
            'expires_at' => $subscription->expires_at->toIso8601String(),
        ]);
    }

    /**
     * S'abonner pour révéler l'identité d'une story
     */
    public function subscribeToStory(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        // Vérifications
        if ($story->user_id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous abonner à votre propre story.',
            ], 422);
        }

        if (!$story->is_active) {
            return response()->json([
                'message' => 'Cette story n\'est plus disponible.',
            ], 422);
        }

        // Vérifier si un abonnement actif existe déjà
        if (PremiumSubscription::hasActiveForStory($user->id, $story->id)) {
            return response()->json([
                'message' => 'Vous avez déjà un abonnement actif pour cette story.',
            ], 422);
        }

        // Créer l'abonnement en attente
        $subscription = PremiumSubscription::create([
            'subscriber_id' => $user->id,
            'target_user_id' => $story->user_id,
            'type' => PremiumSubscription::TYPE_STORY,
            'story_id' => $story->id,
            'amount' => PremiumSubscription::MONTHLY_PRICE,
            'status' => PremiumSubscription::STATUS_PENDING,
        ]);

        // Initier le paiement
        try {
            $paymentResult = $this->paymentService->initiatePayment([
                'amount' => PremiumSubscription::MONTHLY_PRICE,
                'currency' => 'XAF',
                'description' => 'Abonnement Premium - Révélation identité story',
                'reference' => "PREM-{$subscription->id}",
                'user' => $user,
                'metadata' => [
                    'type' => 'subscription',
                    'subscription_id' => $subscription->id,
                    'subscription_type' => 'story',
                ],
            ]);

            $subscription->update(['payment_reference' => $paymentResult['reference']]);

            return response()->json([
                'message' => 'Paiement initié.',
                'subscription_id' => $subscription->id,
                'price' => PremiumSubscription::MONTHLY_PRICE,
                'payment' => $paymentResult,
            ]);
        } catch (\Exception $e) {
            $subscription->delete();

            return response()->json([
                'message' => 'Erreur lors de l\'initiation du paiement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier si l'utilisateur a un premium actif pour un type/id donné
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:message,conversation,story',
            'id' => 'required|integer',
        ]);

        $user = $request->user();
        $type = $request->type;
        $id = $request->id;

        $hasActive = match ($type) {
            'message' => PremiumSubscription::hasActiveForMessage($user->id, $id),
            'conversation' => PremiumSubscription::hasActiveForConversation($user->id, $id),
            'story' => PremiumSubscription::hasActiveForStory($user->id, $id),
        };

        $subscription = null;
        if ($hasActive) {
            $query = PremiumSubscription::bySubscriber($user->id)->active();
            $subscription = match ($type) {
                'message' => $query->where('message_id', $id)->first(),
                'conversation' => $query->where('conversation_id', $id)->first(),
                'story' => $query->where('story_id', $id)->first(),
            };
        }

        return response()->json([
            'has_premium' => $hasActive,
            'subscription' => $subscription ? new PremiumSubscriptionResource($subscription) : null,
            'price' => PremiumSubscription::MONTHLY_PRICE,
        ]);
    }

    /**
     * Obtenir les informations de prix
     */
    public function pricing(): JsonResponse
    {
        return response()->json([
            'monthly_price' => PremiumSubscription::MONTHLY_PRICE,
            'currency' => 'XAF',
            'features' => [
                'Voir l\'identité complète de l\'expéditeur',
                'Accès à l\'historique complet',
                'Badge premium dans la conversation',
                'Renouvellement automatique (optionnel)',
            ],
        ]);
    }
}
