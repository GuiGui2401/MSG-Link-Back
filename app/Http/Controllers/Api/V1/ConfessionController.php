<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Confession\CreateConfessionRequest;
use App\Http\Requests\Confession\ReportConfessionRequest;
use App\Http\Resources\ConfessionResource;
use App\Models\Confession;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfessionController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Feed des confessions publiques approuvées
     */
    public function index(Request $request): JsonResponse
    {
        $confessions = Confession::publicApproved()
            ->with('author:id,first_name')
            ->withCount('likedBy')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Ajouter le flag "liked" pour l'utilisateur connecté
        if ($request->user()) {
            $confessions->getCollection()->transform(function ($confession) use ($request) {
                $confession->is_liked = $confession->isLikedBy($request->user());
                return $confession;
            });
        }

        return response()->json([
            'confessions' => ConfessionResource::collection($confessions),
            'meta' => [
                'current_page' => $confessions->currentPage(),
                'last_page' => $confessions->lastPage(),
                'per_page' => $confessions->perPage(),
                'total' => $confessions->total(),
            ],
        ]);
    }

    /**
     * Mes confessions reçues (privées)
     */
    public function received(Request $request): JsonResponse
    {
        $user = $request->user();

        $confessions = Confession::forRecipient($user->id)
            ->private()
            ->with('author:id,first_name,last_name,username,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'confessions' => ConfessionResource::collection($confessions),
            'meta' => [
                'current_page' => $confessions->currentPage(),
                'last_page' => $confessions->lastPage(),
                'per_page' => $confessions->perPage(),
                'total' => $confessions->total(),
            ],
        ]);
    }

    /**
     * Mes confessions envoyées
     */
    public function sent(Request $request): JsonResponse
    {
        $user = $request->user();

        $confessions = Confession::where('author_id', $user->id)
            ->with('recipient:id,first_name,last_name,username,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'confessions' => ConfessionResource::collection($confessions),
            'meta' => [
                'current_page' => $confessions->currentPage(),
                'last_page' => $confessions->lastPage(),
                'per_page' => $confessions->perPage(),
                'total' => $confessions->total(),
            ],
        ]);
    }

    /**
     * Détail d'une confession
     */
    public function show(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        // Vérifier l'accès
        if ($confession->is_private) {
            if ($confession->recipient_id !== $user?->id && $confession->author_id !== $user?->id) {
                return response()->json([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            // Confession publique : doit être approuvée
            if ($confession->status !== Confession::STATUS_APPROVED && $confession->author_id !== $user?->id) {
                return response()->json([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        // Incrémenter les vues (sauf pour l'auteur)
        if ($confession->author_id !== $user?->id) {
            $confession->incrementViews();
        }

        $confession->load('author:id,first_name,last_name,username,avatar');
        
        if ($user) {
            $confession->is_liked = $confession->isLikedBy($user);
        }

        return response()->json([
            'confession' => new ConfessionResource($confession),
        ]);
    }

    /**
     * Créer une confession
     */
    public function store(CreateConfessionRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $confessionData = [
            'author_id' => $user->id,
            'content' => $validated['content'],
            'type' => $validated['type'],
        ];

        // Si confession privée, vérifier le destinataire
        if ($validated['type'] === Confession::TYPE_PRIVATE) {
            if (empty($validated['recipient_username'])) {
                return response()->json([
                    'message' => 'Un destinataire est requis pour une confession privée.',
                ], 422);
            }

            $recipient = User::where('username', $validated['recipient_username'])->first();

            if (!$recipient) {
                return response()->json([
                    'message' => 'Destinataire non trouvé.',
                ], 404);
            }

            if ($recipient->id === $user->id) {
                return response()->json([
                    'message' => 'Vous ne pouvez pas vous envoyer une confession.',
                ], 422);
            }

            if ($user->isBlockedBy($recipient) || $user->hasBlocked($recipient)) {
                return response()->json([
                    'message' => 'Impossible d\'envoyer une confession à cet utilisateur.',
                ], 422);
            }

            $confessionData['recipient_id'] = $recipient->id;
            $confessionData['status'] = Confession::STATUS_APPROVED; // Privées = auto-approuvées
        } else {
            // Confessions publiques nécessitent modération
            $confessionData['status'] = Confession::STATUS_PENDING;
        }

        $confession = Confession::create($confessionData);

        // Envoyer notification si confession privée
        if ($confession->is_private && $confession->recipient) {
            $this->notificationService->sendNewConfessionNotification($confession);
        }

        return response()->json([
            'message' => $confession->is_public 
                ? 'Confession soumise. Elle sera publiée après modération.'
                : 'Confession envoyée avec succès.',
            'confession' => new ConfessionResource($confession),
        ], 201);
    }

    /**
     * Supprimer une confession
     */
    public function destroy(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        // Seul l'auteur ou le destinataire peut supprimer
        if ($confession->author_id !== $user->id && $confession->recipient_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $confession->delete();

        return response()->json([
            'message' => 'Confession supprimée avec succès.',
        ]);
    }

    /**
     * Liker une confession publique
     */
    public function like(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        if (!$confession->is_public || !$confession->is_approved) {
            return response()->json([
                'message' => 'Action non autorisée.',
            ], 403);
        }

        $liked = $confession->like($user);

        return response()->json([
            'message' => $liked ? 'Confession likée.' : 'Vous avez déjà liké cette confession.',
            'likes_count' => $confession->fresh()->likes_count,
        ]);
    }

    /**
     * Unliker une confession
     */
    public function unlike(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        $unliked = $confession->unlike($user);

        return response()->json([
            'message' => $unliked ? 'Like retiré.' : 'Vous n\'avez pas liké cette confession.',
            'likes_count' => $confession->fresh()->likes_count,
        ]);
    }

    /**
     * Signaler une confession
     */
    public function report(ReportConfessionRequest $request, Confession $confession): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($confession->isReportedBy($user)) {
            return response()->json([
                'message' => 'Vous avez déjà signalé cette confession.',
            ], 422);
        }

        $confession->report($user, $validated['reason'], $validated['description'] ?? null);

        return response()->json([
            'message' => 'Signalement envoyé. Merci pour votre vigilance.',
        ], 201);
    }

    /**
     * Révéler l'identité de l'auteur (pour confession privée + premium)
     */
    public function reveal(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        if ($confession->recipient_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if ($confession->is_identity_revealed) {
            return response()->json([
                'message' => 'L\'identité a déjà été révélée.',
                'author' => $confession->author_info,
            ]);
        }

        // Vérifier abonnement premium
        // Note: Pour les confessions, on utilise un système similaire aux messages
        // L'utilisateur doit avoir un abonnement actif

        // TODO: Implémenter la logique premium pour les confessions

        return response()->json([
            'message' => 'Un abonnement premium est requis.',
            'requires_premium' => true,
        ], 402);
    }

    /**
     * Statistiques des confessions
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'received_count' => Confession::forRecipient($user->id)->count(),
            'sent_count' => Confession::where('author_id', $user->id)->count(),
            'public_approved_count' => Confession::where('author_id', $user->id)
                ->publicApproved()
                ->count(),
            'pending_count' => Confession::where('author_id', $user->id)
                ->pending()
                ->count(),
        ]);
    }
}
