<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\SendMessageRequest;
use App\Http\Requests\Message\ReportMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\AnonymousMessage;
use App\Models\User;
use App\Models\PremiumSubscription;
use App\Models\WalletTransaction;
use App\Events\MessageSent;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private \App\Services\Notifications\NexahService $nexahService
    ) {}

    /**
     * Liste des messages reçus
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = AnonymousMessage::forRecipient($user->id)
            ->with(['sender:id,first_name,last_name,username,avatar', 'replyToMessage'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'messages' => MessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Messages envoyés
     */
    public function sent(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = AnonymousMessage::fromSender($user->id)
            ->with(['recipient:id,first_name,last_name,username,avatar', 'replyToMessage'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'messages' => MessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Détail d'un message
     */
    public function show(Request $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();

        // Vérifier les autorisations
        if ($message->recipient_id !== $user->id && $message->sender_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Marquer comme lu si destinataire
        if ($message->recipient_id === $user->id) {
            $message->markAsRead();
            $message->refresh();
        }

        return response()->json([
            'message' => new MessageResource($message),
        ]);
    }

    /**
     * Envoyer un message anonyme
     */
    public function send(SendMessageRequest $request, string $username): JsonResponse
    {
        $sender = $request->user();
        $recipient = User::where('username', $username)->firstOrFail();

        // Vérifications
        // if ($sender->id === $recipient->id) {
        //     return response()->json([
        //         'message' => 'Vous ne pouvez pas vous envoyer un message.',
        //     ], 422);
        // }


        if ($recipient->is_banned) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est plus disponible.',
            ], 422);
        }

        // Vérifier si bloqué
        if ($sender->isBlockedBy($recipient) || $sender->hasBlocked($recipient)) {
            return response()->json([
                'message' => 'Impossible d\'envoyer un message à cet utilisateur.',
            ], 422);
        }

        // Vérifier le message original si c'est une réponse
        $validated = $request->validated();
        if (!empty($validated['reply_to_message_id'])) {
            $originalMessage = AnonymousMessage::find($validated['reply_to_message_id']);

            // Vérifier que le message original existe et que l'utilisateur actuel est le destinataire
            if (!$originalMessage || $originalMessage->recipient_id !== $sender->id) {
                return response()->json([
                    'message' => 'Vous ne pouvez répondre qu\'aux messages que vous avez reçus.',
                ], 422);
            }

            // Le destinataire de la réponse est l'expéditeur du message original
            $recipient = User::find($originalMessage->sender_id);
        }

        // Créer le message
        $message = AnonymousMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'content' => $validated['content'],
            'reply_to_message_id' => $validated['reply_to_message_id'] ?? null,
        ]);

        // Déclencher l'événement
        try {
            event(new MessageSent($message));
        } catch (\Illuminate\Broadcasting\BroadcastException $e) {
            \Log::warning('Broadcast MessageSent failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Envoyer notification
        $this->notificationService->sendNewMessageNotification($message);

        // Envoyer SMS au destinataire si numéro valide
        if ($recipient->phone && strlen(trim($recipient->phone)) > 5) {
            try {
                $smsMessage = "📩 Nouveau message anonyme sur Weylo!\n\n"
                    . "« " . substr($validated['content'], 0, 100)
                    . (strlen($validated['content']) > 100 ? '...' : '') . " »\n\n"
                    . "Connectez-vous pour lire: " . config('app.frontend_url');

                $this->nexahService->sendSms(
                    $recipient->phone,
                    $smsMessage
                );

                \Log::info("SMS envoyé au destinataire {$recipient->username} ({$recipient->phone})");
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi du SMS: " . $e->getMessage());
                // Ne pas bloquer l'envoi du message si l'SMS échoue
            }
        }

        return response()->json([
            'message' => 'Message envoyé avec succès.',
            'data' => new MessageResource($message),
        ], 201);
    }

    /**
     * Révéler l'identité de l'expéditeur
     */
    public function reveal(Request $request, AnonymousMessage $message): JsonResponse
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
                'sender' => $message->sender_info,
            ]);
        }

        // Récupérer le prix de révélation depuis les settings
        $revealPrice = reveal_anonymous_price();

        // Vérifier si l'utilisateur a un solde suffisant
        if ($user->wallet_balance < $revealPrice) {
            return response()->json([
                'message' => 'Solde insuffisant pour révéler l\'identité.',
                'requires_payment' => true,
                'price' => $revealPrice,
                'current_balance' => $user->wallet_balance,
            ], 402);
        }

        // Effectuer la transaction dans une transaction DB
        try {
            DB::beginTransaction();

            // Débiter le wallet de l'utilisateur
            $balanceBefore = $user->wallet_balance;
            $user->wallet_balance -= $revealPrice;
            $user->save();

            // Créer la transaction wallet
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $revealPrice,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->wallet_balance,
                'description' => 'Révélation d\'identité d\'un message anonyme',
                'reference' => 'REVEAL_' . $message->id . '_' . time(),
                'transactionable_type' => AnonymousMessage::class,
                'transactionable_id' => $message->id,
            ]);

            // Révéler l'identité
            $message->revealIdentity();

            DB::commit();

            return response()->json([
                'message' => 'Identité révélée avec succès.',
                'sender' => $message->fresh()->sender_info,
                'new_balance' => $user->wallet_balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la révélation d\'identité: ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la révélation de l\'identité.',
            ], 500);
        }
    }

    /**
     * Supprimer un message
     */
    public function destroy(Request $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();

        // Seul le destinataire peut supprimer le message
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'message' => 'Message supprimé avec succès.',
        ]);
    }

    /**
     * Signaler un message
     */
    public function report(ReportMessageRequest $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Vérifier que c'est le destinataire
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Vérifier si déjà signalé
        if ($message->isReportedBy($user)) {
            return response()->json([
                'message' => 'Vous avez déjà signalé ce message.',
            ], 422);
        }

        $report = $message->report($user, $validated['reason'], $validated['description'] ?? null);

        return response()->json([
            'message' => 'Signalement envoyé. Merci pour votre vigilance.',
        ], 201);
    }

    /**
     * Statistiques des messages
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'received_count' => $user->receivedMessages()->count(),
            'sent_count' => $user->sentMessages()->count(),
            'unread_count' => $user->receivedMessages()->unread()->count(),
            'revealed_count' => $user->receivedMessages()->where('is_identity_revealed', true)->count(),
        ]);
    }

    /**
     * Marquer tous les messages comme lus
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->receivedMessages()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'Tous les messages ont été marqués comme lus.',
        ]);
    }

    /**
     * Démarrer une conversation à partir d'un message anonyme
     */
    public function startConversation(Request $request, AnonymousMessage $message): JsonResponse
    {
        $user = $request->user();

        // Vérifier que c'est bien le destinataire du message
        if ($message->recipient_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Obtenir l'expéditeur du message
        $sender = User::findOrFail($message->sender_id);

        // Vérifier les blocages
        if ($user->isBlockedBy($sender) || $user->hasBlocked($sender)) {
            return response()->json([
                'message' => 'Impossible de démarrer une conversation avec cet utilisateur.',
            ], 422);
        }

        // Créer ou obtenir la conversation
        $conversation = $user->getOrCreateConversationWith($sender);

        // Charger les relations nécessaires
        $conversation->load([
            'participantOne:id,first_name,last_name,username,avatar,last_seen_at',
            'participantTwo:id,first_name,last_name,username,avatar,last_seen_at',
        ]);

        $conversation->other_participant = $conversation->getOtherParticipant($user);
        $conversation->unread_count = $conversation->unreadCountFor($user);

        return response()->json([
            'message' => 'Conversation créée avec succès.',
            'conversation' => new \App\Http\Resources\ConversationResource($conversation),
        ], 201);
    }
}
