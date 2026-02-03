<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendChatMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\ConversationIdentityReveal;
use App\Models\WalletTransaction;
use App\Models\Payment;
use App\Models\Setting;
use App\Events\ChatMessageSent;
use App\Services\NotificationService;
use App\Services\LygosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AnonymousMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private LygosService $lygosService
    ) {}

    /**
     * Liste des conversations
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::forUser($user->id)
            ->with([
                'participantOne:id,first_name,last_name,username,avatar,last_seen_at',
                'participantTwo:id,first_name,last_name,username,avatar,last_seen_at',
                'lastMessage',
            ])
            ->withRecentActivity()
            ->paginate($request->get('per_page', 20));

        // Transformer pour ajouter des infos supplémentaires
        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            $conversation->other_participant = $conversation->getOtherParticipant($user);
            $conversation->unread_count = $conversation->unreadCountFor($user);
            $conversation->has_premium = $conversation->hasPremiumSubscription($user);
            return $conversation;
        });

        return response()->json([
            'conversations' => ConversationResource::collection($conversations),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * Détail d'une conversation
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $conversation->load([
            'participantOne:id,first_name,last_name,username,avatar,last_seen_at',
            'participantTwo:id,first_name,last_name,username,avatar,last_seen_at',
        ]);

        $conversation->other_participant = $conversation->getOtherParticipant($user);
        $conversation->unread_count = $conversation->unreadCountFor($user);
        $conversation->has_premium = $conversation->hasPremiumSubscription($user);

        return response()->json([
            'conversation' => new ConversationResource($conversation),
        ]);
    }

    /**
     * Créer ou obtenir une conversation avec un utilisateur
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|exists:users,username',
        ]);

        $user = $request->user();
        $otherUser = User::where('username', $request->username)->firstOrFail();

        if ($user->id === $otherUser->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas démarrer une conversation avec vous-même.',
            ], 422);
        }

        if ($user->isBlockedBy($otherUser) || $user->hasBlocked($otherUser)) {
            return response()->json([
                'message' => 'Impossible de démarrer une conversation avec cet utilisateur.',
            ], 422);
        }

        $conversation = $user->getOrCreateConversationWith($otherUser);

        $conversation->load([
            'participantOne:id,first_name,last_name,username,avatar,last_seen_at',
            'participantTwo:id,first_name,last_name,username,avatar,last_seen_at',
        ]);

        $conversation->other_participant = $conversation->getOtherParticipant($user);

        return response()->json([
            'conversation' => new ConversationResource($conversation),
        ], 201);
    }

    /**
     * Messages d'une conversation
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $messages = $conversation->messages()
            ->with([
                'sender:id,first_name,last_name,username,avatar',
                'giftTransaction.gift',
                'anonymousMessage:id,content,created_at',
                'replyToMessage:id,content,type,image_url,voice_url,video_url,created_at',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'messages' => ChatMessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Supprimer un message (uniquement l'expéditeur)
     */
    public function deleteMessage(Request $request, Conversation $conversation, ChatMessage $message): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if ($message->conversation_id !== $conversation->id) {
            return response()->json([
                'message' => 'Message non trouvé.',
            ], 404);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'message' => 'Message supprimé.',
        ]);
    }

    /**
     * Envoyer un message dans une conversation
     */
    public function sendMessage(SendChatMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $otherUser = $conversation->getOtherParticipant($user);

        // Vérifier les blocages
        if ($user->isBlockedBy($otherUser) || $user->hasBlocked($otherUser)) {
            return response()->json([
                'message' => 'Impossible d\'envoyer un message à cet utilisateur.',
            ], 422);
        }

        $validated = $request->validated();

        $messageData = [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $validated['content'] ?? '',
            'type' => ChatMessage::TYPE_TEXT,
        ];

        if (!empty($validated['reply_to_id'])) {
            $replyToMessage = $conversation->messages()
                ->where('id', $validated['reply_to_id'])
                ->first();
            if ($replyToMessage) {
                $messageData['reply_to_message_id'] = $replyToMessage->id;
            } else {
                $anonymousMessage = AnonymousMessage::find($validated['reply_to_id']);
                if ($anonymousMessage) {
                    $messageData['anonymous_message_id'] = $anonymousMessage->id;
                }
            }
        }

        if ($request->hasFile('voice')) {
            $voice = $request->file('voice');
            $path = $voice->store('chat_messages/voices', 'public');
            \Illuminate\Support\Facades\Log::debug('[ChatController] Voice Uploaded', [
                'path' => $path,
                'original_size' => $voice->getSize(),
                'saved_size' => \Illuminate\Support\Facades\Storage::disk('public')->size($path),
            ]);
            $messageData['voice_url'] = $path;
            $messageData['type'] = ChatMessage::TYPE_VOICE;
            $messageData['voice_effect'] = $validated['voice_effect'] ?? null;
        }

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $path = $video->store('chat_messages/videos', 'public');
            \Illuminate\Support\Facades\Log::debug('[ChatController] Video Uploaded', [
                'path' => $path,
                'original_size' => $video->getSize(),
                'saved_size' => \Illuminate\Support\Facades\Storage::disk('public')->size($path),
            ]);
            $messageData['video_url'] = $path;
            $messageData['type'] = ChatMessage::TYPE_VIDEO;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('chat_messages/images', 'public');
            \Illuminate\Support\Facades\Log::debug('[ChatController] Image Uploaded', [
                'path' => $path,
                'original_size' => $image->getSize(),
                'saved_size' => \Illuminate\Support\Facades\Storage::disk('public')->size($path),
            ]);
            $messageData['image_url'] = $path;
            $messageData['type'] = ChatMessage::TYPE_IMAGE;
        }

        // Créer le message
        $message = ChatMessage::create($messageData);

        // Mettre à jour la conversation
        $conversation->updateAfterMessage();

        // Charger les relations (incluant conversation pour ChatMessageResource)
        $message->load([
            'conversation',
            'sender:id,first_name,last_name,username,avatar',
            'anonymousMessage:id,content,created_at',
            'replyToMessage:id,content,type,image_url,voice_url,video_url,created_at',
        ]);

        // Diffuser l'événement en temps réel
        try {
            \Log::info('📤 [CHAT] Broadcasting ChatMessageSent', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'channel' => 'conversation.' . $conversation->id,
            ]);

            broadcast(new ChatMessageSent($message))->toOthers();

            \Log::info('✅ [CHAT] ChatMessageSent broadcasted successfully');
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas l'envoi du message
            \Log::error('❌ [CHAT] Broadcasting failed for message: ' . $e->getMessage());
            \Log::error($e);
        }

        // Notification push si l'autre utilisateur n'est pas en ligne
        if (!$otherUser->is_online) {
            $this->notificationService->sendChatMessageNotification($message);
        }

        return response()->json([
            'message' => new ChatMessageResource($message),
        ], 201);
    }

    /**
     * Marquer les messages comme lus
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $conversation->markAllAsReadFor($user);

        return response()->json([
            'message' => 'Messages marqués comme lus.',
        ]);
    }

    /**
     * Supprimer une conversation (pour l'utilisateur uniquement)
     */
    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Soft delete - on garde la conversation mais on la masque pour l'utilisateur
        // Alternative: vraie suppression si les deux ont supprimé
        $conversation->delete();

        return response()->json([
            'message' => 'Conversation supprimée.',
        ]);
    }

    /**
     * Statistiques du chat
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::forUser($user->id);

        return response()->json([
            'total_conversations' => $conversations->count(),
            'active_conversations' => $conversations->clone()
                ->where('last_message_at', '>=', now()->subDays(7))
                ->count(),
            'total_messages_sent' => ChatMessage::where('sender_id', $user->id)->count(),
            'unread_conversations' => $conversations->clone()
                ->get()
                ->filter(fn($c) => $c->unreadCountFor($user) > 0)
                ->count(),
            'streaks' => [
                'active' => $conversations->clone()->withStreak()->count(),
                'max_streak' => $conversations->clone()->max('streak_count') ?? 0,
            ],
        ]);
    }

    /**
     * Obtenir le statut en ligne d'un utilisateur
     */
    public function userStatus(Request $request, string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        return response()->json([
            'username' => $user->username,
            'is_online' => $user->is_online,
            'last_seen_at' => $user->last_seen_at?->toIso8601String(),
        ]);
    }

    /**
     * Mettre à jour mon statut (appelé périodiquement par le frontend)
     */
    public function updatePresence(Request $request): JsonResponse
    {
        $request->user()->updateLastSeen();

        return response()->json([
            'status' => 'online',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Révéler l'identité de l'autre participant dans une conversation
     */
    public function revealIdentity(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Vérifications
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $otherUser = $conversation->getOtherParticipant($user);

        // Vérifier si déjà révélé
        if ($conversation->hasRevealedIdentityFor($user, $otherUser)) {
            return response()->json([
                'message' => 'L\'identité a déjà été révélée.',
                'other_participant' => [
                    'id' => $otherUser->id,
                    'username' => $otherUser->username,
                    'first_name' => $otherUser->first_name,
                    'last_name' => $otherUser->last_name,
                    'avatar' => $otherUser->avatar,
                    'is_premium' => $otherUser->is_premium,
                ],
            ]);
        }

        // Récupérer le prix de révélation depuis les settings
        $revealPrice = Setting::get('reveal_anonymous_price', 1000);

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
            $walletTransaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $revealPrice,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->wallet_balance,
                'description' => 'Révélation d\'identité dans une conversation',
                'reference' => 'REVEAL_CONV_' . $conversation->id . '_' . time(),
                'transactionable_type' => Conversation::class,
                'transactionable_id' => $conversation->id,
            ]);

            // Créer la révélation d'identité
            ConversationIdentityReveal::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'revealed_user_id' => $otherUser->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'revealed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Identité révélée avec succès.',
                'other_participant' => [
                    'id' => $otherUser->id,
                    'username' => $otherUser->username,
                    'first_name' => $otherUser->first_name,
                    'last_name' => $otherUser->last_name,
                    'avatar' => $otherUser->avatar,
                    'is_premium' => $otherUser->is_premium,
                ],
                'new_balance' => $user->wallet_balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la révélation d\'identité dans conversation: ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors de la révélation de l\'identité.',
            ], 500);
        }
    }

    /**
     * Initier le paiement Lygos pour révéler l'identité dans une conversation
     */
    public function initiateRevealPayment(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est participant de la conversation
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas participant de cette conversation.',
            ], 403);
        }

        $otherUser = $conversation->getOtherParticipant($user);

        // Vérifier que l'identité n'est pas déjà révélée
        if ($conversation->hasRevealedIdentityFor($user, $otherUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà révélé l\'identité de cet utilisateur.',
            ], 400);
        }

        // Récupérer le prix depuis les settings
        $price = Setting::get('reveal_anonymous_price', 1000);

        // Valider les données de paiement
        // Validation flexible pour accepter différents formats de numéros internationaux
        // Format attendu: code pays (3-4 chiffres) + numéro local (6-10 chiffres)
        // Exemples: 237651234567 (Cameroun), 2250701234567 (Côte d'Ivoire), etc.
        $request->validate([
            'phone_number' => [
                'required',
                'string',
                'regex:/^(229|226|237|242|225|243|241|254|250|221|255|260)[0-9]{6,10}$/',
            ],
            'operator' => 'required|string|in:MTN_MOMO_CMR,ORANGE_MONEY_CMR',
        ]);

        try {
            DB::beginTransaction();

            // Annuler les anciens paiements en attente pour cette conversation
            Payment::where('user_id', $user->id)
                ->where('type', 'reveal_identity')
                ->whereIn('status', ['pending', 'processing'])
                ->get()
                ->filter(function ($p) use ($conversation) {
                    return isset($p->metadata['conversation_id']) && $p->metadata['conversation_id'] == $conversation->id;
                })
                ->each(function ($oldPayment) {
                    $oldPayment->update([
                        'status' => 'cancelled',
                        'failure_reason' => 'New payment initiated',
                    ]);
                    Log::info('🔄 [REVEAL CONV] Ancien paiement annulé', [
                        'payment_id' => $oldPayment->id,
                        'order_id' => $oldPayment->provider_reference,
                    ]);
                });

            // Créer une référence unique
            $reference = 'REVEAL-CONV-' . strtoupper(Str::random(12));

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
                    'context' => 'conversation', // Pour différencier des messages
                    'conversation_id' => $conversation->id,
                    'revealed_user_id' => $otherUser->id,
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

            Log::info('✅ [REVEAL CONV] Paiement initié', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
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

            Log::error('❌ [REVEAL CONV] Erreur lors de l\'initiation du paiement', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
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
    public function checkRevealPaymentStatus(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est participant de la conversation
        if (!$conversation->hasParticipant($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas participant de cette conversation.',
            ], 403);
        }

        $otherUser = $conversation->getOtherParticipant($user);

        // Vérifier si l'identité est déjà révélée
        if ($conversation->hasRevealedIdentityFor($user, $otherUser)) {
            return response()->json([
                'success' => true,
                'message' => 'L\'identité a déjà été révélée.',
                'data' => [
                    'status' => 'revealed',
                    'other_participant' => [
                        'id' => $otherUser->id,
                        'username' => $otherUser->username,
                        'first_name' => $otherUser->first_name,
                        'last_name' => $otherUser->last_name,
                        'full_name' => $otherUser->full_name,
                        'avatar_url' => $otherUser->avatar_url,
                    ],
                ],
            ]);
        }

        // Récupérer le paiement le plus récent en cours pour cette conversation
        $payment = Payment::where('user_id', $user->id)
            ->where('type', 'reveal_identity')
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($p) use ($conversation) {
                return isset($p->metadata['conversation_id']) && $p->metadata['conversation_id'] == $conversation->id;
            })
            ->first();

        if (!$payment) {
            Log::warning('⚠️ [REVEAL CONV] Aucun paiement trouvé', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Aucun paiement en cours trouvé pour cette conversation.',
            ], 404);
        }

        Log::info('🔍 [REVEAL CONV] Vérification du paiement', [
            'payment_id' => $payment->id,
            'order_id' => $payment->provider_reference,
            'current_status' => $payment->status,
            'created_at' => $payment->created_at,
        ]);

        try {
            // Vérifier le statut auprès de Lygos
            $lygosStatus = $this->lygosService->getTransactionStatus($payment->provider_reference);

            Log::info('🔍 [REVEAL CONV] Statut Lygos', [
                'payment_id' => $payment->id,
                'order_id' => $payment->provider_reference,
                'lygos_status' => $lygosStatus['status'] ?? 'unknown',
            ]);

            // Si le paiement est réussi
            $successStatuses = ['success', 'completed'];
            if (isset($lygosStatus['status']) && in_array(strtolower($lygosStatus['status']), $successStatuses)) {
                DB::beginTransaction();

                // Mettre à jour le paiement
                $payment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // Créer la révélation d'identité
                ConversationIdentityReveal::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'revealed_user_id' => $otherUser->id,
                    'payment_id' => $payment->id,
                    'revealed_at' => now(),
                ]);

                DB::commit();

                Log::info('✅ [REVEAL CONV] Identité révélée', [
                    'payment_id' => $payment->id,
                    'conversation_id' => $conversation->id,
                    'revealed_user_id' => $otherUser->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement confirmé. Identité révélée.',
                    'data' => [
                        'status' => 'revealed',
                        'other_participant' => [
                            'id' => $otherUser->id,
                            'username' => $otherUser->username,
                            'first_name' => $otherUser->first_name,
                            'last_name' => $otherUser->last_name,
                            'full_name' => $otherUser->full_name,
                            'avatar_url' => $otherUser->avatar_url,
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

            // Paiement toujours en attente
            return response()->json([
                'success' => true,
                'message' => 'Paiement en cours de traitement.',
                'data' => [
                    'status' => 'processing',
                    'payment_link' => $payment->metadata['lygos_link'] ?? null,
                    'lygos_status' => $lygosStatus['status'] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Si la transaction n'est pas trouvée
            if (str_contains($errorMessage, 'Transaction not found') || str_contains($errorMessage, 'TRANSACTION_NOT_FOUND')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement en attente. Veuillez compléter le paiement sur votre téléphone.',
                    'data' => [
                        'status' => 'processing',
                        'payment_link' => $payment->metadata['lygos_link'] ?? null,
                    ],
                ]);
            }

            // Si timeout de Lygos
            if (str_contains($errorMessage, 'LYGOS_TIMEOUT')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vérification en cours. La connexion avec Lygos est lente, veuillez patienter...',
                    'data' => [
                        'status' => 'processing',
                        'payment_link' => $payment->metadata['lygos_link'] ?? null,
                        'lygos_timeout' => true,
                    ],
                ]);
            }

            Log::error('❌ [REVEAL CONV] Erreur lors de la vérification du statut', [
                'payment_id' => $payment->id,
                'error' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du paiement',
            ], 500);
        }
    }
}
