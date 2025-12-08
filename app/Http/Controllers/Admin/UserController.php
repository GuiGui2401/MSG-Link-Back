<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,banned,all',
            'role' => 'nullable|in:user,moderator,admin',
            'sort_by' => 'nullable|in:created_at,last_seen_at,wallet_balance',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = User::query();

        // Recherche
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filtre par statut
        if ($request->status === 'active') {
            $query->active();
        } elseif ($request->status === 'banned') {
            $query->banned();
        }

        // Filtre par rôle
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'users' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Détail d'un utilisateur
     */
    public function show(User $user): JsonResponse
    {
        $user->loadCount([
            'receivedMessages',
            'sentMessages',
            'confessionsReceived',
            'confessionsWritten',
            'giftsReceived',
            'giftsSent',
            'reports',
        ]);

        return response()->json([
            'user' => new UserResource($user),
            'stats' => [
                'messages_received' => $user->received_messages_count,
                'messages_sent' => $user->sent_messages_count,
                'confessions_received' => $user->confessions_received_count,
                'confessions_written' => $user->confessions_written_count,
                'gifts_received' => $user->gifts_received_count,
                'gifts_sent' => $user->gifts_sent_count,
                'reports_made' => $user->reports_count,
                'wallet_balance' => $user->wallet_balance,
            ],
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|unique:users,phone,' . $user->id,
            'role' => 'nullable|in:user,moderator,admin',
            'is_verified' => 'nullable|boolean',
        ]);

        $admin = $request->user();
        $oldValues = $user->only(array_keys($request->all()));

        $user->update($request->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'role',
            'is_verified',
        ]));

        // Log de l'action admin
        AdminLog::log($admin, 'update_user', $user, $oldValues, $request->all());

        return response()->json([
            'message' => 'Utilisateur mis à jour.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Bannir un utilisateur
     */
    public function ban(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $admin = $request->user();

        // Ne pas pouvoir se bannir soi-même
        if ($user->id === $admin->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous bannir vous-même.',
            ], 422);
        }

        // Ne pas bannir un admin
        if ($user->is_admin && !$admin->is_admin) {
            return response()->json([
                'message' => 'Vous ne pouvez pas bannir un administrateur.',
            ], 403);
        }

        if ($user->is_banned) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà banni.',
            ], 422);
        }

        $user->ban($request->reason);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_BAN_USER, $user, [], [
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Utilisateur banni avec succès.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Débannir un utilisateur
     */
    public function unban(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();

        if (!$user->is_banned) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas banni.',
            ], 422);
        }

        $oldReason = $user->banned_reason;
        $user->unban();

        // Log
        AdminLog::log($admin, AdminLog::ACTION_UNBAN_USER, $user, [
            'banned_reason' => $oldReason,
        ]);

        return response()->json([
            'message' => 'Utilisateur débanni avec succès.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();

        // Ne pas pouvoir se supprimer soi-même
        if ($user->id === $admin->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        // Ne pas supprimer un admin (sauf si super admin)
        if ($user->is_admin) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer un administrateur.',
            ], 403);
        }

        // Log avant suppression
        AdminLog::log($admin, AdminLog::ACTION_DELETE_USER, $user, $user->toArray());

        // Révoquer tous les tokens
        $user->tokens()->delete();

        // Soft delete
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    /**
     * Voir l'historique des actions admin sur un utilisateur
     */
    public function adminLogs(User $user): JsonResponse
    {
        $logs = AdminLog::where('model_type', User::class)
            ->where('model_id', $user->id)
            ->with('admin:id,first_name,last_name,username')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * Statistiques globales des utilisateurs
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total' => User::count(),
            'active' => User::active()->count(),
            'banned' => User::banned()->count(),
            'verified' => User::where('is_verified', true)->count(),
            'by_role' => [
                'user' => User::where('role', 'user')->count(),
                'moderator' => User::where('role', 'moderator')->count(),
                'admin' => User::where('role', 'admin')->count(),
            ],
            'registrations' => [
                'today' => User::whereDate('created_at', today())->count(),
                'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
                'this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
        ]);
    }
}
