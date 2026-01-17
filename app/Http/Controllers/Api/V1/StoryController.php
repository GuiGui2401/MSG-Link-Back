<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoryResource;
use App\Models\PremiumSubscription;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    /**
     * Feed des stories actives
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Marquer les stories expirées
        Story::markExpiredStories();

        // Récupérer les stories actives groupées par utilisateur
        $stories = Story::active()
            ->with('user:id,first_name,last_name,username,avatar')
            ->orderBy('created_at', 'desc')
            ->get();

        // Grouper par utilisateur et ajouter le flag "viewed"
        $storiesByUser = $stories->groupBy('user_id')->map(function ($userStories) use ($user) {
            $firstStory = $userStories->first();
            $allViewed = $userStories->every(function ($story) use ($user) {
                return $story->isViewedBy($user);
            });

            // Si l'utilisateur créateur de la story est supprimé, ne pas afficher la story
            if (!$firstStory->user) {
                return null;
            }

            // Vérifier si l'utilisateur a payé pour voir l'identité
            $isOwner = $user && $user->id === $firstStory->user_id;
            $hasSubscription = $user && PremiumSubscription::hasActiveForStory($user->id, $firstStory->id);
            $shouldRevealIdentity = $isOwner || $hasSubscription;

            // Préparer l'aperçu de la première story
            $preview = [
                'type' => $firstStory->type,
            ];

            if ($firstStory->type === 'image') {
                $preview['media_url'] = $firstStory->media_full_url;
            } elseif ($firstStory->type === 'text') {
                $preview['content'] = $firstStory->content;
                $preview['background_color'] = $firstStory->background_color;
            }

            return [
                'user' => [
                    'id' => $shouldRevealIdentity ? $firstStory->user->id : null,
                    'username' => $shouldRevealIdentity ? $firstStory->user->username : 'Anonyme',
                    'full_name' => $shouldRevealIdentity ? $firstStory->user->full_name : 'Utilisateur Anonyme',
                    'avatar_url' => $shouldRevealIdentity ? $firstStory->user->avatar_url : 'https://ui-avatars.com/api/?name=Anonyme&background=667eea&color=fff',
                ],
                'real_user_id' => $firstStory->user->id, // Toujours retourner l'ID réel pour pouvoir charger les stories
                'is_anonymous' => !$shouldRevealIdentity,
                'preview' => $preview,
                'stories_count' => $userStories->count(),
                'latest_story_at' => $firstStory->created_at->toIso8601String(),
                'all_viewed' => $allViewed,
                'has_new' => !$allViewed,
            ];
        })->filter()->values(); // Filtrer les valeurs null

        return response()->json([
            'stories' => $storiesByUser,
        ]);
    }

    /**
     * Stories d'un utilisateur spécifique par username
     */
    public function userStories(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user();

        $user = User::where('username', $username)->withoutTrashed()->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        return $this->fetchUserStoriesByIdInternal($request, $user->id);
    }

    /**
     * Stories d'un utilisateur spécifique par ID
     */
    public function userStoriesById(Request $request, int $userId): JsonResponse
    {
        return $this->fetchUserStoriesByIdInternal($request, $userId);
    }

    /**
     * Méthode privée pour récupérer les stories d'un utilisateur par ID
     */
    private function fetchUserStoriesByIdInternal(Request $request, int $userId): JsonResponse
    {
        $viewer = $request->user();

        $user = User::withoutTrashed()->find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Récupérer les stories actives de l'utilisateur avec les relations
        $stories = Story::forUser($user->id)
            ->with('user:id,first_name,last_name,username,avatar')
            ->active()
            ->orderBy('created_at', 'asc')
            ->get();

        // Ajouter le flag "viewed" pour chaque story
        $stories->transform(function ($story) use ($viewer) {
            $story->is_viewed = $story->isViewedBy($viewer);
            return $story;
        });

        // Vérifier si on doit révéler l'identité
        $isOwner = $viewer && $viewer->id === $user->id;
        $hasSubscription = $viewer && $stories->isNotEmpty() &&
            PremiumSubscription::hasActiveForStory($viewer->id, $stories->first()->id);
        $shouldRevealIdentity = $isOwner || $hasSubscription;

        return response()->json([
            'user' => [
                'id' => $shouldRevealIdentity ? $user->id : null,
                'username' => $shouldRevealIdentity ? $user->username : 'Anonyme',
                'full_name' => $shouldRevealIdentity ? $user->full_name : 'Utilisateur Anonyme',
                'avatar_url' => $shouldRevealIdentity ? $user->avatar_url : 'https://ui-avatars.com/api/?name=Anonyme&background=667eea&color=fff',
            ],
            'is_anonymous' => !$shouldRevealIdentity,
            'stories' => StoryResource::collection($stories),
        ]);
    }

    /**
     * Mes stories (actives uniquement)
     */
    public function myStories(Request $request): JsonResponse
    {
        $user = $request->user();

        // Marquer les stories expirées d'abord
        Story::markExpiredStories();

        // Récupérer uniquement les stories actives (non expirées)
        $stories = Story::forUser($user->id)
            ->active()
            ->with('viewedBy:id,first_name,last_name,username,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'stories' => StoryResource::collection($stories),
            'meta' => [
                'current_page' => $stories->currentPage(),
                'last_page' => $stories->lastPage(),
                'per_page' => $stories->perPage(),
                'total' => $stories->total(),
            ],
        ]);
    }

    /**
     * Détail d'une story
     */
    public function show(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        if ($story->is_expired) {
            return response()->json([
                'message' => 'Cette story a expiré.',
            ], 404);
        }

        // Marquer comme vue (sauf pour le propriétaire)
        if ($story->user_id !== $user->id) {
            $story->markAsViewedBy($user);
        }

        $story->load('user:id,first_name,last_name,username,avatar');
        $story->is_viewed = $story->isViewedBy($user);

        return response()->json([
            'story' => new StoryResource($story),
        ]);
    }

    /**
     * Créer une story
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:image,video,text',
            'media' => 'required_if:type,image,video|file|max:51200', // 50MB max pour vidéos
            'content' => 'required_if:type,text|string|max:500',
            'background_color' => 'nullable|string|max:7', // Format hex: #RRGGBB
            'duration' => 'nullable|integer|min:3|max:60', // 3 à 60 secondes pour les vidéos
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $storyData = [
            'user_id' => $user->id,
            'type' => $validated['type'],
            'duration' => $validated['duration'] ?? 5,
            'expires_at' => now()->addHours(24), // Expire après 24h
        ];

        // Gestion du média (image ou vidéo)
        if (in_array($validated['type'], ['image', 'video']) && $request->hasFile('media')) {
            $media = $request->file('media');

            if ($validated['type'] === 'image') {
                // Valider le type MIME pour les images
                if (!in_array($media->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    return response()->json([
                        'message' => 'Le fichier doit être une image (JPEG, PNG, GIF, WebP).',
                    ], 422);
                }
            } else {
                // Valider le type MIME pour les vidéos
                $allowedVideoMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska'];
                if (!in_array($media->getMimeType(), $allowedVideoMimes)) {
                    return response()->json([
                        'message' => 'Le fichier doit être une vidéo (MP4, MOV, AVI, WebM, MKV).',
                    ], 422);
                }
            }

            // Sauvegarder le média
            $path = $media->store('stories/' . $user->id, 'public');
            $storyData['media_url'] = $path;
        }

        // Gestion du contenu texte
        if ($validated['type'] === 'text') {
            $storyData['content'] = $validated['content'];
            $storyData['background_color'] = $validated['background_color'] ?? '#6366f1';
        }

        $story = Story::create($storyData);

        return response()->json([
            'message' => 'Story créée avec succès.',
            'story' => new StoryResource($story->load('user')),
        ], 201);
    }

    /**
     * Supprimer une story
     */
    public function destroy(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        // Seul le propriétaire peut supprimer
        if ($story->user_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Supprimer le média du stockage
        if ($story->media_url) {
            Storage::disk('public')->delete($story->media_url);
        }
        if ($story->thumbnail_url) {
            Storage::disk('public')->delete($story->thumbnail_url);
        }

        $story->delete();

        return response()->json([
            'message' => 'Story supprimée avec succès.',
        ]);
    }

    /**
     * Marquer une story comme vue
     */
    public function markAsViewed(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        if ($story->is_expired) {
            return response()->json([
                'message' => 'Cette story a expiré.',
            ], 404);
        }

        // Ne pas compter les vues du propriétaire
        if ($story->user_id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas voir votre propre story.',
            ], 422);
        }

        $viewed = $story->markAsViewedBy($user);

        return response()->json([
            'message' => $viewed ? 'Story vue.' : 'Déjà vue.',
            'views_count' => $story->views_count,
        ]);
    }

    /**
     * Obtenir les viewers d'une story
     */
    public function viewers(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        // Seul le propriétaire peut voir les viewers
        if ($story->user_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $viewers = $story->getViewers();

        return response()->json([
            'viewers' => $viewers->map(fn($viewer) => [
                'id' => $viewer->id,
                'username' => $viewer->username,
                'full_name' => $viewer->full_name,
                'avatar_url' => $viewer->avatar_url,
                'viewed_at' => $viewer->pivot->created_at->toIso8601String(),
            ]),
            'total_views' => $story->views_count,
        ]);
    }

    /**
     * Statistiques des stories
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalStories = Story::forUser($user->id)->count();
        $activeStories = Story::forUser($user->id)->active()->count();
        $totalViews = Story::forUser($user->id)->sum('views_count');

        return response()->json([
            'total_stories' => $totalStories,
            'active_stories' => $activeStories,
            'expired_stories' => $totalStories - $activeStories,
            'total_views' => $totalViews,
        ]);
    }

    // ==================== COMMENTS ====================

    /**
     * Obtenir les commentaires d'une story
     */
    public function getComments(Request $request, Story $story): JsonResponse
    {
        $comments = $story->comments()
            ->with(['user:id,first_name,last_name,username,avatar,is_premium', 'replies.user:id,first_name,last_name,username,avatar,is_premium'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'comments' => $comments->map(function ($comment) {
                return $this->formatComment($comment);
            }),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Ajouter un commentaire à une story
     */
    public function addComment(Request $request, Story $story): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:story_comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Vérifier que le parent appartient à la même story
        if (isset($validated['parent_id'])) {
            $parentComment = StoryComment::find($validated['parent_id']);
            if ($parentComment->story_id !== $story->id) {
                return response()->json([
                    'message' => 'Le commentaire parent n\'appartient pas à cette story.',
                ], 422);
            }
        }

        $comment = StoryComment::create([
            'story_id' => $story->id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load('user:id,first_name,last_name,username,avatar,is_premium');

        return response()->json([
            'message' => 'Commentaire ajouté avec succès.',
            'comment' => $this->formatComment($comment),
        ], 201);
    }

    /**
     * Supprimer un commentaire
     */
    public function deleteComment(Request $request, Story $story, StoryComment $comment): JsonResponse
    {
        $user = $request->user();

        // Seul l'auteur du commentaire ou le propriétaire de la story peut supprimer
        if ($comment->user_id !== $user->id && $story->user_id !== $user->id) {
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
     * Formater un commentaire pour la réponse
     */
    private function formatComment(StoryComment $comment): array
    {
        $formatted = [
            'id' => $comment->id,
            'content' => $comment->content,
            'likes_count' => $comment->likes_count,
            'created_at' => $comment->created_at->toIso8601String(),
            'user' => $comment->user ? [
                'id' => $comment->user->id,
                'username' => $comment->user->username,
                'full_name' => $comment->user->full_name,
                'avatar_url' => $comment->user->avatar_url,
                'is_premium' => $comment->user->is_premium,
            ] : null,
        ];

        if ($comment->relationLoaded('replies')) {
            $formatted['replies'] = $comment->replies->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'likes_count' => $reply->likes_count,
                    'created_at' => $reply->created_at->toIso8601String(),
                    'user' => $reply->user ? [
                        'id' => $reply->user->id,
                        'username' => $reply->user->username,
                        'full_name' => $reply->user->full_name,
                        'avatar_url' => $reply->user->avatar_url,
                        'is_premium' => $reply->user->is_premium,
                    ] : null,
                ];
            });
        }

        return $formatted;
    }
}
