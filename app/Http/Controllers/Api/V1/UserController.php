<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UpdateSettingsRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserPublicResource;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Rechercher des utilisateurs
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|min:2|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = User::active()
            ->select(['id', 'first_name', 'last_name', 'username', 'avatar', 'bio']);

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $users = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'users' => UserPublicResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Profil public d'un utilisateur
     */
    public function show(string $username): JsonResponse
    {
        $user = User::where('username', $username)
            ->active()
            ->firstOrFail();

        return response()->json([
            'user' => new UserPublicResource($user),
        ]);
    }

    /**
     * Profil public d'un utilisateur par ID
     */
    public function showById(int $id): JsonResponse
    {
        $user = User::where('id', $id)
            ->active()
            ->firstOrFail();

        return response()->json([
            'user' => new UserPublicResource($user),
        ]);
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Mettre à jour les paramètres
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $currentSettings = $user->settings ?? [];
        $newSettings = array_merge($currentSettings, $validated);

        $user->update(['settings' => $newSettings]);

        return response()->json([
            'message' => 'Paramètres mis à jour avec succès.',
            'settings' => $user->fresh()->settings,
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // Révoquer tous les autres tokens (déconnexion des autres appareils)
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }

    /**
     * Upload d'avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Supprimer l'ancien avatar si existe
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Stocker le nouvel avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar mis à jour avec succès.',
            'avatar_url' => $user->fresh()->avatar_url,
        ]);
    }

    /**
     * Supprimer l'avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json([
            'message' => 'Avatar supprimé.',
            'avatar_url' => $user->fresh()->avatar_url,
        ]);
    }

    /**
     * Dashboard utilisateur (statistiques)
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            // Récupérer les stats avec gestion d'erreurs pour chaque relation
            $stats = [
                'messages' => [
                    'received' => $user->receivedMessages()->count() ?? 0,
                    'sent' => $user->sentMessages()->count() ?? 0,
                    'unread' => $user->receivedMessages()->where('is_read', false)->count() ?? 0,
                ],
                'confessions' => [
                    'received' => $user->confessionsReceived()->count() ?? 0,
                    'sent' => $user->confessionsWritten()->count() ?? 0,
                ],
                'conversations' => [
                    'total' => $user->conversations()->count() ?? 0,
                    'active' => $user->conversations()
                        ->where('last_message_at', '>=', now()->subDays(7))
                        ->count() ?? 0,
                ],
                'gifts' => [
                    'received' => $user->giftsReceived()->where('status', 'completed')->count() ?? 0,
                    'sent' => $user->giftsSent()->where('status', 'completed')->count() ?? 0,
                ],
                'wallet' => [
                    'balance' => $user->wallet_balance ?? 0,
                    'formatted' => $user->formatted_balance ?? '0 FCFA',
                ],
            ];

            return response()->json([
                'user' => new UserResource($user),
                'stats' => $stats,
                'share_link' => $user->profile_url,
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération du dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bloquer un utilisateur
     */
    public function block(Request $request, string $username): JsonResponse
    {
        $user = $request->user();
        $userToBlock = User::where('username', $username)->firstOrFail();

        if ($user->id === $userToBlock->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous bloquer vous-même.',
            ], 422);
        }

        if ($user->hasBlocked($userToBlock)) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà bloqué.',
            ], 422);
        }

        $user->blockedUsers()->attach($userToBlock->id);

        return response()->json([
            'message' => 'Utilisateur bloqué avec succès.',
        ]);
    }

    /**
     * Débloquer un utilisateur
     */
    public function unblock(Request $request, string $username): JsonResponse
    {
        $user = $request->user();
        $userToUnblock = User::where('username', $username)->firstOrFail();

        if (!$user->hasBlocked($userToUnblock)) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas bloqué.',
            ], 422);
        }

        $user->blockedUsers()->detach($userToUnblock->id);

        return response()->json([
            'message' => 'Utilisateur débloqué.',
        ]);
    }

    /**
     * Liste des utilisateurs bloqués
     */
    public function blockedUsers(Request $request): JsonResponse
    {
        $user = $request->user();

        $blockedUsers = $user->blockedUsers()
            ->select(['users.id', 'first_name', 'last_name', 'username', 'avatar'])
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'users' => UserPublicResource::collection($blockedUsers),
            'meta' => [
                'current_page' => $blockedUsers->currentPage(),
                'last_page' => $blockedUsers->lastPage(),
                'per_page' => $blockedUsers->perPage(),
                'total' => $blockedUsers->total(),
            ],
        ]);
    }

    /**
     * Enregistrer le token FCM pour les notifications push
     */
    public function saveFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->token,
        ]);

        return response()->json([
            'message' => 'Token FCM enregistré.',
        ]);
    }

    /**
     * Supprimer le compte
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect.',
            ], 422);
        }

        // Révoquer tous les tokens
        $user->tokens()->delete();

        // Soft delete
        $user->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès.',
        ]);
    }

    /**
     * Obtenir le lien de partage
     */
    public function shareLink(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'link' => $user->profile_url,
            'username' => $user->username,
            'share_text' => "Écris-moi un message anonyme 👇",
            'share_options' => [
                'whatsapp' => "https://wa.me/?text=" . urlencode("Écris-moi un message anonyme 👇 " . $user->profile_url),
                'facebook' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($user->profile_url),
                'twitter' => "https://twitter.com/intent/tweet?text=" . urlencode("Écris-moi un message anonyme 👇 " . $user->profile_url),
            ],
        ]);
    }

    /**
     * Récupérer uniquement les statistiques de l'utilisateur
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur non authentifié',
                ], 401);
            }

            // Récupérer les stats avec gestion d'erreurs pour chaque relation
            $stats = [
                'messages' => [
                    'received' => $user->receivedMessages()->count() ?? 0,
                    'sent' => $user->sentMessages()->count() ?? 0,
                    'unread' => $user->receivedMessages()->where('is_read', false)->count() ?? 0,
                ],
                'confessions' => [
                    'received' => $user->confessionsReceived()->count() ?? 0,
                    'sent' => $user->confessionsWritten()->count() ?? 0,
                ],
                'conversations' => [
                    'total' => $user->conversations()->count() ?? 0,
                    'active' => $user->conversations()
                        ->where('last_message_at', '>=', now()->subDays(7))
                        ->count() ?? 0,
                ],
                'gifts' => [
                    'received' => $user->giftsReceived()->where('status', 'completed')->count() ?? 0,
                    'sent' => $user->giftsSent()->where('status', 'completed')->count() ?? 0,
                ],
                'wallet' => [
                    'balance' => $user->wallet_balance ?? 0,
                    'formatted' => $user->formatted_balance ?? '0 FCFA',
                ],
            ];

            return response()->json([
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            \Log::error('Stats error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
