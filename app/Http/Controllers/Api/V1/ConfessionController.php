<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Confession\CreateConfessionRequest;
use App\Http\Requests\Confession\ReportConfessionRequest;
use App\Http\Resources\ConfessionResource;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\PostPromotion;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $activePromotions = PostPromotion::query()
            ->selectRaw('confession_id, MAX(id) as id')
            ->where('status', PostPromotion::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->groupBy('confession_id');

        $confessions = Confession::publicApproved()
            ->leftJoinSub($activePromotions, 'pp', function ($join) {
                $join->on('confessions.id', '=', 'pp.confession_id');
            })
            ->select('confessions.*')
            ->with('author:id,first_name,last_name,username,avatar,is_premium,is_verified')
            ->withCount(['likedBy', 'comments'])
            ->orderByRaw('pp.id IS NULL')
            ->orderByRaw('COALESCE(liked_by_count, 0) DESC')
            ->orderBy('confessions.created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Ajouter le flag "liked" pour l'utilisateur connecté
        if ($request->user()) {
            $confessions->getCollection()->transform(function ($confession) use ($request) {
                $confession->is_liked = $confession->isLikedBy($request->user());
                return $confession;
            });
        } else {
            // Si pas connecté, is_liked = false
            $confessions->getCollection()->transform(function ($confession) {
                $confession->is_liked = false;
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
     * Confessions d'un utilisateur spécifique (publiques et approuvées)
     */
    public function userConfessions(Request $request, int $userId): JsonResponse
    {
        $confessions = Confession::where('author_id', $userId)
            ->public()
            ->approved()
            ->where('is_anonymous', false) // Ne pas inclure les posts anonymes
            ->with('author:id,first_name,last_name,username,avatar,is_premium,is_verified')
            ->withCount(['likedBy', 'comments'])
            ->orderByRaw('COALESCE(liked_by_count, 0) DESC')
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
     * Confessions d'un utilisateur par username (publiques et approuvées)
     */
    public function userConfessionsByUsername(Request $request, string $username): JsonResponse
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        return $this->userConfessions($request, $user->id);
    }

    /**
     * Confessions likées par l'utilisateur courant
     */
    public function liked(Request $request): JsonResponse
    {
        $user = $request->user();

        $confessions = Confession::whereHas('likedBy', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->public()
            ->approved()
            ->with('author:id,first_name,last_name,username,avatar,is_premium,is_verified')
            ->withCount(['likedBy', 'comments'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Marquer toutes comme likées puisqu'on les récupère depuis les likes de l'utilisateur
        $confessions->getCollection()->transform(function ($confession) {
            $confession->is_liked = true;
            return $confession;
        });

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
        $user = $request->user() ?? auth('sanctum')->user();

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
            $confession->incrementViews($user);
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
     * Marquer une confession comme vue (pour vidéos)
     */
    public function view(Request $request, Confession $confession): JsonResponse
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
            if ($confession->status !== Confession::STATUS_APPROVED && $confession->author_id !== $user?->id) {
                return response()->json([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        if ($user && $confession->author_id !== $user->id) {
            $confession->incrementViews($user);
        }

        return response()->json([
            'views_count' => $confession->fresh()->views_count ?? 0,
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
            'content' => $validated['content'] ?? '',
            'type' => $validated['type'],
            'is_anonymous' => $request->boolean('is_anonymous', false),
        ];

        // Gérer l'upload d'image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('confessions', 'public');
            $confessionData['image'] = $path;
        }

        // Gérer l'upload de vidéo
        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $path = $video->store('confessions/videos', 'public');
            $confessionData['video'] = $path;
        }

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
            // Confessions publiques = auto-approuvées
            $confessionData['status'] = Confession::STATUS_APPROVED;
        }

        $confession = Confession::create($confessionData);

        // Envoyer notification si confession privée
        if ($confession->is_private && $confession->recipient) {
            $this->notificationService->sendNewConfessionNotification($confession);
        }

        return response()->json([
            'message' => $confession->is_public
                ? 'Confession publique publiée avec succès.'
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

        // Vérifier si la publication a une promotion active
        $hasActivePromotion = \App\Models\PostPromotion::where('confession_id', $confession->id)
            ->active()
            ->exists();

        if ($hasActivePromotion) {
            return response()->json([
                'message' => 'Impossible de supprimer cette publication car elle est actuellement en cours de promotion. Attendez la fin de la promotion ou annulez-la d\'abord.',
                'has_active_promotion' => true,
            ], 422);
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

    /**
     * Récupérer les commentaires d'une confession
     */
    public function getComments(Request $request, Confession $confession): JsonResponse
    {
        // Vérifier l'accès à la confession
        if ($confession->is_private) {
            $user = $request->user();
            if ($confession->recipient_id !== $user?->id && $confession->author_id !== $user?->id) {
                return response()->json([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            // Confession publique : doit être approuvée
            if ($confession->status !== Confession::STATUS_APPROVED) {
                return response()->json([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        $currentUserId = $request->user()?->id;

        $comments = $confession->comments()
            ->with([
                'author:id,first_name,username,avatar',
                'parent.author:id,first_name,username,avatar',
            ])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) use ($currentUserId) {
                $parent = $comment->parent;
                $parentPayload = null;
                if ($parent) {
                    $parentPayload = [
                        'id' => $parent->id,
                        'content' => $parent->getDecryptedAttribute('content') ?? $parent->content,
                        'is_anonymous' => $parent->is_anonymous,
                        'author' => $parent->is_anonymous ? [
                            'name' => 'Anonyme',
                            'initial' => '?',
                            'avatar_url' => null,
                        ] : [
                            'id' => $parent->author->id,
                            'name' => $parent->author->first_name,
                            'username' => $parent->author->username,
                            'initial' => $parent->author->initial,
                            'avatar_url' => $parent->author->avatar_url,
                        ],
                    ];
                }
                return [
                    'id' => $comment->id,
                    'content' => $comment->getDecryptedAttribute('content') ?? $comment->content,
                    'is_anonymous' => $comment->is_anonymous,
                    'parent_id' => $comment->parent_id,
                    'parent' => $parentPayload,
                    'author' => $comment->is_anonymous ? [
                        'name' => 'Anonyme',
                        'initial' => '?',
                        'avatar_url' => null,
                    ] : [
                        'id' => $comment->author->id,
                        'name' => $comment->author->first_name,
                        'username' => $comment->author->username,
                        'initial' => $comment->author->initial,
                        'avatar_url' => $comment->author->avatar_url,
                    ],
                    'media_url' => $comment->media_url,
                    'media_full_url' => $comment->media_full_url,
                    'media_type' => $comment->media_type,
                    'created_at' => $comment->created_at,
                    'is_mine' => $comment->author_id === $currentUserId,
                ];
            });

        return response()->json([
            'comments' => $comments,
            'total' => $comments->count(),
        ]);
    }

    /**
     * Ajouter un commentaire
     */
    public function addComment(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        // Vérifier l'accès à la confession
        if ($confession->is_private) {
            if ($confession->recipient_id !== $user->id && $confession->author_id !== $user->id) {
                return response()->json([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            // Confession publique : doit être approuvée
            if ($confession->status !== Confession::STATUS_APPROVED) {
                return response()->json([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        $validated = $request->validate([
            'content' => 'required_without:image|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'is_anonymous' => 'boolean',
            'parent_id' => 'nullable|integer',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $parentComment = null;
        if ($parentId) {
            $parentComment = ConfessionComment::where('confession_id', $confession->id)
                ->where('id', $parentId)
                ->first();
            if (!$parentComment) {
                return response()->json([
                    'message' => 'Commentaire parent introuvable.',
                ], 404);
            }
        }

        $comment = $confession->comments()->create([
            'author_id' => $user->id,
            'parent_id' => $parentComment?->id,
            'content' => $validated['content'] ?? '',
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('confessions/comments', 'public');
            $comment->media_url = $path;
            $comment->media_type = $image->getMimeType();
            $comment->save();
        }

        $comment->load('author:id,first_name,username,avatar');

        return response()->json([
            'message' => 'Commentaire ajouté avec succès.',
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->getDecryptedAttribute('content') ?? $comment->content,
                'is_anonymous' => $comment->is_anonymous,
                'parent_id' => $comment->parent_id,
                'parent' => $parentComment ? [
                    'id' => $parentComment->id,
                    'content' => $parentComment->getDecryptedAttribute('content') ?? $parentComment->content,
                    'is_anonymous' => $parentComment->is_anonymous,
                    'author' => $parentComment->is_anonymous ? [
                        'name' => 'Anonyme',
                        'initial' => '?',
                        'avatar_url' => null,
                    ] : [
                        'id' => $parentComment->author->id,
                        'name' => $parentComment->author->first_name,
                        'username' => $parentComment->author->username,
                        'initial' => $parentComment->author->initial,
                        'avatar_url' => $parentComment->author->avatar_url,
                    ],
                ] : null,
                'media_url' => $comment->media_url,
                'media_full_url' => $comment->media_full_url,
                'media_type' => $comment->media_type,
                'author' => $comment->is_anonymous ? [
                    'name' => 'Anonyme',
                    'initial' => '?',
                    'avatar_url' => null,
                ] : [
                    'id' => $comment->author->id,
                    'name' => $comment->author->first_name,
                    'username' => $comment->author->username,
                    'initial' => $comment->author->initial,
                    'avatar_url' => $comment->author->avatar_url,
                ],
                'created_at' => $comment->created_at,
                'is_mine' => true,
            ],
        ], 201);
    }

    /**
     * Supprimer un commentaire
     */
    public function deleteComment(Request $request, Confession $confession, ConfessionComment $comment): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le commentaire appartient bien à cette confession
        if ($comment->confession_id !== $confession->id) {
            return response()->json([
                'message' => 'Commentaire non trouvé.',
            ], 404);
        }

        // Seul l'auteur du commentaire peut le supprimer
        if ($comment->author_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Commentaire supprimé avec succès.',
        ]);
    }

    /**
     * Marquer une impression sponsorisée
     */
    public function promotionImpression(Request $request, Confession $confession): JsonResponse
    {
        $promotion = $confession->activePromotion();
        if ($promotion) {
            $promotion->incrementImpressions();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Marquer un clic sponsorisé
     */
    public function promotionClick(Request $request, Confession $confession): JsonResponse
    {
        $promotion = $confession->activePromotion();
        if ($promotion) {
            $promotion->incrementClicks();
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
