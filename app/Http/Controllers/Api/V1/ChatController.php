<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendChatMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Events\ChatMessageSent;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
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

        // Créer le message
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->validated()['content'],
            'type' => ChatMessage::TYPE_TEXT,
            'anonymous_message_id' => $request->validated()['reply_to_id'] ?? null,
        ]);

        // Mettre à jour la conversation
        $conversation->updateAfterMessage();

        // Charger les relations
        $message->load([
            'sender:id,first_name,last_name,username,avatar',
            'anonymousMessage:id,content,created_at'
        ]);

        // Diffuser l'événement en temps réel
        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas l'envoi du message
            \Log::warning('Broadcasting failed for message: ' . $e->getMessage());
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
}
