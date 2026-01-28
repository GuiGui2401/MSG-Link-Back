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

    private function jsonResponse(array $data, int $status = 200): JsonResponse
    {
        $options = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE;
        return response()->json($data, $status, [], $options);
    }

    /**
     * Feed des confessions publiques approuvées
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 20);
        $page = max(1, (int) $request->get('page', 1));
        $sponsorBoost = 2.0;
        $sponsorSpacing = 5;

        $activePromotions = PostPromotion::query()
            ->selectRaw('confession_id, MAX(id) as id')
            ->where('status', PostPromotion::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->groupBy('confession_id');

        $commentsCountSql = '(select count(*) from confession_comments where confessions.id = confession_comments.confession_id and confession_comments.deleted_at is null)';
        $reportsCountSql = '(select count(*) from reports where confessions.id = reports.reportable_id and reports.reportable_type = \'' . addslashes(Confession::class) . '\' and reports.status != \'' . \App\Models\Report::STATUS_DISMISSED . '\')';
        $feedScoreSql = '(
                (
                    (COALESCE(confessions.likes_count, 0) * 1) +
                    (COALESCE(' . $commentsCountSql . ', 0) * 3) +
                    (COALESCE(confessions.shares_count, 0) * 5)
                ) - (COALESCE(' . $reportsCountSql . ', 0) * 10)
            )
            * (CASE WHEN COALESCE(' . $reportsCountSql . ', 0) >= 10 THEN 0.5 ELSE 1 END)
            * (CASE
                WHEN TIMESTAMPDIFF(HOUR, confessions.created_at, NOW()) < 2 THEN 1.5
                WHEN TIMESTAMPDIFF(HOUR, confessions.created_at, NOW()) < 6 THEN 1.3
                WHEN TIMESTAMPDIFF(HOUR, confessions.created_at, NOW()) < 24 THEN 1.1
                ELSE 1.0
            END)';

        $baseQuery = Confession::publicApproved()
            ->leftJoinSub($activePromotions, 'pp', function ($join) {
                $join->on('confessions.id', '=', 'pp.confession_id');
            })
            ->select('confessions.*')
            ->addSelect('pp.id as promotion_id')
            ->with('author:id,first_name,last_name,username,avatar,is_premium,is_verified')
            ->withCount(['comments'])
            ->selectRaw($feedScoreSql . ' as feed_score')
            ->selectRaw(
                '(' . $feedScoreSql . ' * (CASE WHEN pp.id IS NULL THEN 1 ELSE ' . $sponsorBoost . ' END)) as boosted_score'
            );

        $total = (clone $baseQuery)->count('confessions.id');
        $targetCount = $page * $perPage;
        $chunkSize = max(50, $perPage * 3);

        $final = [];
        $pendingSponsored = [];
        $lastSponsoredIndex = null;
        $offset = 0;
        $hasMore = true;

        while (count($final) < $targetCount && $hasMore) {
            $chunk = (clone $baseQuery)
                ->orderByRaw('boosted_score DESC')
                ->orderBy('confessions.created_at', 'desc')
                ->offset($offset)
                ->limit($chunkSize)
                ->get();

            $countChunk = $chunk->count();
            $offset += $countChunk;
            $hasMore = $countChunk === $chunkSize;

            foreach ($chunk as $confession) {
                $isSponsored = !is_null($confession->promotion_id);

                if ($isSponsored) {
                    if ($this->canInsertSponsored(count($final), $lastSponsoredIndex, $sponsorSpacing)) {
                        $final[] = $confession;
                        $lastSponsoredIndex = count($final) - 1;
                    } else {
                        $pendingSponsored[] = $confession;
                    }
                } else {
                    $final[] = $confession;
                }

                while (!empty($pendingSponsored)
                    && $this->canInsertSponsored(count($final), $lastSponsoredIndex, $sponsorSpacing)
                    && count($final) < $targetCount) {
                    $final[] = array_shift($pendingSponsored);
                    $lastSponsoredIndex = count($final) - 1;
                }

                if (count($final) >= $targetCount) {
                    break;
                }
            }
        }

        $start = ($page - 1) * $perPage;
        $pageItems = array_slice($final, $start, $perPage);
        $confessions = collect($pageItems);

        // Ajouter le flag "liked" pour l'utilisateur connecté
        if ($request->user()) {
            $confessions->transform(function ($confession) use ($request) {
                $confession->is_liked = $confession->isLikedBy($request->user());
                return $confession;
            });
        } else {
            // Si pas connecté, is_liked = false
            $confessions->transform(function ($confession) {
                $confession->is_liked = false;
                return $confession;
            });
        }

        return $this->jsonResponse([
            'confessions' => ConfessionResource::collection($confessions),
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    private function canInsertSponsored(
        int $currentIndex,
        ?int $lastSponsoredIndex,
        int $spacing
    ): bool {
        if ($lastSponsoredIndex === null) {
            return true;
        }
        return ($currentIndex - $lastSponsoredIndex) >= $spacing;
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

        return $this->jsonResponse([
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

        return $this->jsonResponse([
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

        return $this->jsonResponse([
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
            return $this->jsonResponse([
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

        return $this->jsonResponse([
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
                return $this->JsonResponse([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            // Confession publique : doit être approuvée
            if ($confession->status !== Confession::STATUS_APPROVED && $confession->author_id !== $user?->id) {
                return $this->JsonResponse([
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

        return $this->JsonResponse([
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
                return $this->JsonResponse([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            if ($confession->status !== Confession::STATUS_APPROVED && $confession->author_id !== $user?->id) {
                return $this->JsonResponse([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        if ($user && $confession->author_id !== $user->id) {
            $confession->incrementViews($user);
        }

        return $this->JsonResponse([
            'views_count' => $confession->fresh()->views_count ?? 0,
        ]);
    }

    /**
     * Marquer une confession comme partagée
     */
    public function share(Request $request, Confession $confession): JsonResponse
    {
        $user = $request->user();

        // Vérifier l'accès
        if ($confession->is_private) {
            if ($confession->recipient_id !== $user?->id && $confession->author_id !== $user?->id) {
                return $this->JsonResponse([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            if ($confession->status !== Confession::STATUS_APPROVED && $confession->author_id !== $user?->id) {
                return $this->JsonResponse([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        $confession->incrementShares($user);

        return $this->JsonResponse([
            'shares_count' => $confession->fresh()->shares_count ?? 0,
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
            \Illuminate\Support\Facades\Log::debug('[ConfessionController] Image Uploaded', [
                'path' => $path,
                'original_size' => $image->getSize(),
                'saved_size' => \Illuminate\Support\Facades\Storage::disk('public')->size($path),
            ]);
            $confessionData['image'] = $path;
        }

        // Gérer l'upload de vidéo
        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $path = $video->store('confessions/videos', 'public');
            \Illuminate\Support\Facades\Log::debug('[ConfessionController] Video Uploaded', [
                'path' => $path,
                'original_size' => $video->getSize(),
                'saved_size' => \Illuminate\Support\Facades\Storage::disk('public')->size($path),
            ]);
            $confessionData['video'] = $path;
        }

        // Si confession privée, vérifier le destinataire
        if ($validated['type'] === Confession::TYPE_PRIVATE) {
            if (empty($validated['recipient_username'])) {
                return $this->JsonResponse([
                    'message' => 'Un destinataire est requis pour une confession privée.',
                ], 422);
            }

            $recipient = User::where('username', $validated['recipient_username'])->first();

            if (!$recipient) {
                return $this->JsonResponse([
                    'message' => 'Destinataire non trouvé.',
                ], 404);
            }

            if ($recipient->id === $user->id) {
                return $this->JsonResponse([
                    'message' => 'Vous ne pouvez pas vous envoyer une confession.',
                ], 422);
            }

            if ($user->isBlockedBy($recipient) || $user->hasBlocked($recipient)) {
                return $this->JsonResponse([
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

        return $this->JsonResponse([
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
            return $this->JsonResponse([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Vérifier si la publication a une promotion active
        $hasActivePromotion = \App\Models\PostPromotion::where('confession_id', $confession->id)
            ->active()
            ->exists();

        if ($hasActivePromotion) {
            return $this->JsonResponse([
                'message' => 'Impossible de supprimer cette publication car elle est actuellement en cours de promotion. Attendez la fin de la promotion ou annulez-la d\'abord.',
                'has_active_promotion' => true,
            ], 422);
        }

        $confession->delete();

        return $this->JsonResponse([
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
            return $this->JsonResponse([
                'message' => 'Action non autorisée.',
            ], 403);
        }

        $liked = $confession->like($user);

        return $this->JsonResponse([
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

        return $this->JsonResponse([
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
            return $this->JsonResponse([
                'message' => 'Vous avez déjà signalé cette confession.',
            ], 422);
        }

        $confession->report($user, $validated['reason'], $validated['description'] ?? null);

        return $this->JsonResponse([
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
            return $this->JsonResponse([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if ($confession->is_identity_revealed) {
            return $this->JsonResponse([
                'message' => 'L\'identité a déjà été révélée.',
                'author' => $confession->author_info,
            ]);
        }

        // Vérifier abonnement premium
        // Note: Pour les confessions, on utilise un système similaire aux messages
        // L'utilisateur doit avoir un abonnement actif

        // TODO: Implémenter la logique premium pour les confessions

        return $this->JsonResponse([
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

        return $this->JsonResponse([
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
                return $this->JsonResponse([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            // Confession publique : doit être approuvée
            if ($confession->status !== Confession::STATUS_APPROVED) {
                return $this->JsonResponse([
                    'message' => 'Confession non disponible.',
                ], 404);
            }
        }

        $currentUserId = $request->user()?->id;

        $commentsQuery = $confession->comments()
            ->with([
                'author:id,first_name,username,avatar',
                'parent.author:id,first_name,username,avatar',
            ])
            ->orderBy('created_at', 'asc');

        if ($currentUserId) {
            $commentsQuery->withExists([
                'likedBy as is_liked' => function ($query) use ($currentUserId) {
                    $query->where('user_id', $currentUserId);
                },
            ]);
        }

        $comments = $commentsQuery->get()
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
                    'likes_count' => $comment->likes_count ?? 0,
                    'is_liked' => $currentUserId ? (bool) $comment->is_liked : false,
                    'created_at' => $comment->created_at,
                    'is_mine' => $comment->author_id === $currentUserId,
                ];
            });

        return $this->JsonResponse([
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
                return $this->JsonResponse([
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        } else {
            // Confession publique : doit être approuvée
            if ($confession->status !== Confession::STATUS_APPROVED) {
                return $this->JsonResponse([
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
                return $this->JsonResponse([
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

        return $this->JsonResponse([
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
                'likes_count' => $comment->likes_count ?? 0,
                'is_liked' => false,
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
     * Liker un commentaire
     */
    public function likeComment(Request $request, Confession $confession, ConfessionComment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->confession_id !== $confession->id) {
            return $this->JsonResponse([
                'message' => 'Commentaire non trouvé.',
            ], 404);
        }

        $alreadyLiked = $comment->likedBy()->where('user_id', $user->id)->exists();
        if (!$alreadyLiked) {
            $comment->likedBy()->attach($user->id);
            $comment->likes_count = ($comment->likes_count ?? 0) + 1;
            $comment->save();
        }

        return $this->JsonResponse([
            'likes_count' => $comment->likes_count ?? 0,
            'is_liked' => true,
        ]);
    }

    /**
     * Retirer un like d'un commentaire
     */
    public function unlikeComment(Request $request, Confession $confession, ConfessionComment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->confession_id !== $confession->id) {
            return $this->JsonResponse([
                'message' => 'Commentaire non trouvé.',
            ], 404);
        }

        $alreadyLiked = $comment->likedBy()->where('user_id', $user->id)->exists();
        if ($alreadyLiked) {
            $comment->likedBy()->detach($user->id);
            $comment->likes_count = max(0, ($comment->likes_count ?? 0) - 1);
            $comment->save();
        }

        return $this->JsonResponse([
            'likes_count' => $comment->likes_count ?? 0,
            'is_liked' => false,
        ]);
    }

    /**
     * Supprimer un commentaire
     */
    public function deleteComment(Request $request, Confession $confession, ConfessionComment $comment): JsonResponse
    {
        $user = $request->user();

        // Vérifier que le commentaire appartient bien à cette confession
        if ($comment->confession_id !== $confession->id) {
            return $this->JsonResponse([
                'message' => 'Commentaire non trouvé.',
            ], 404);
        }

        // Seul l'auteur du commentaire peut le supprimer
        if ($comment->author_id !== $user->id) {
            return $this->JsonResponse([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $comment->delete();

        return $this->JsonResponse([
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

        return $this->JsonResponse([
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

        return $this->JsonResponse([
            'success' => true,
        ]);
    }
}
