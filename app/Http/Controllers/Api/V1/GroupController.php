<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\User;
use App\Events\GroupMessageSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Liste des groupes de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $groups = Group::whereHas('activeMembers', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['lastMessage'])
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Ajouter des infos supplémentaires
        $groups->getCollection()->transform(function ($group) use ($user) {
            $group->unread_count = $group->unreadCountFor($user);
            $group->is_creator = $group->isCreator($user);
            $group->is_admin = $group->isAdmin($user);
            return $group;
        });

        return response()->json([
            'groups' => $groups->items(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /**
     * Créer un nouveau groupe
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'max_members' => 'nullable|integer|min:2|max:200',
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            // Créer le groupe
            $group = Group::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'creator_id' => $user->id,
                'invite_code' => Group::generateInviteCode(),
                'is_public' => $validated['is_public'] ?? false,
                'max_members' => $validated['max_members'] ?? Group::MAX_MEMBERS_DEFAULT,
                'members_count' => 1,
            ]);

            // Ajouter le créateur comme admin
            $member = $group->members()->create([
                'user_id' => $user->id,
                'role' => GroupMember::ROLE_ADMIN,
                'joined_at' => now(),
            ]);

            // Message système de bienvenue avec nom anonyme
            GroupMessage::createSystemMessage($group, "Groupe créé par {$member->anonymous_name}");

            DB::commit();

            return response()->json([
                'message' => 'Groupe créé avec succès.',
                'group' => $group,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création du groupe.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'un groupe
     */
    public function show(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->hasMember($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $group->load([
            'lastMessage',
        ]);

        $group->unread_count = $group->unreadCountFor($user);
        $group->is_creator = $group->isCreator($user);
        $group->is_admin = $group->isAdmin($user);

        return response()->json([
            'group' => $group,
        ]);
    }

    /**
     * Mettre à jour un groupe
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        // Seul le créateur ou un admin peut modifier
        if (!$group->isCreator($user) && !$group->isAdmin($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'sometimes|boolean',
            'max_members' => 'sometimes|integer|min:2|max:200',
        ]);

        $group->update($validated);

        return response()->json([
            'message' => 'Groupe mis à jour avec succès.',
            'group' => $group->fresh(),
        ]);
    }

    /**
     * Supprimer un groupe (seul le créateur)
     */
    public function destroy(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->isCreator($user)) {
            return response()->json([
                'message' => 'Seul le créateur peut supprimer le groupe.',
            ], 403);
        }

        $group->delete();

        return response()->json([
            'message' => 'Groupe supprimé avec succès.',
        ]);
    }

    /**
     * Rejoindre un groupe via code d'invitation
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invite_code' => 'required|string|size:8',
        ]);

        $user = $request->user();
        $group = Group::where('invite_code', $validated['invite_code'])->firstOrFail();

        // Vérifier si déjà membre
        if ($group->hasMember($user)) {
            return response()->json([
                'message' => 'Vous êtes déjà membre de ce groupe.',
            ], 422);
        }

        // Vérifier la limite de membres
        if (!$group->canAcceptMoreMembers()) {
            return response()->json([
                'message' => 'Le groupe a atteint sa limite de membres.',
            ], 422);
        }

        // Ajouter le membre
        $member = $group->addMember($user);

        if (!$member) {
            return response()->json([
                'message' => 'Impossible de rejoindre le groupe.',
            ], 500);
        }

        return response()->json([
            'message' => 'Vous avez rejoint le groupe avec succès.',
            'group' => $group,
        ], 201);
    }

    /**
     * Quitter un groupe
     */
    public function leave(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->hasMember($user)) {
            return response()->json([
                'message' => 'Vous n\'êtes pas membre de ce groupe.',
            ], 422);
        }

        // Le créateur ne peut pas quitter son groupe
        if ($group->isCreator($user)) {
            return response()->json([
                'message' => 'Le créateur ne peut pas quitter le groupe. Supprimez-le ou transférez la propriété.',
            ], 422);
        }

        $group->removeMember($user);

        return response()->json([
            'message' => 'Vous avez quitté le groupe.',
        ]);
    }

    /**
     * Liste des messages d'un groupe
     */
    public function messages(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->hasMember($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $messages = $group->messages()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        // Ajouter l'info si c'est le message de l'utilisateur
        $messages->getCollection()->transform(function ($message) use ($user) {
            $message->is_own = $message->sender_id === $user->id;
            return $message;
        });

        return response()->json([
            'messages' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Envoyer un message dans un groupe
     */
    public function sendMessage(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->hasMember($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'reply_to_message_id' => 'nullable|exists:group_messages,id',
        ]);

        // Créer le message
        $message = GroupMessage::create([
            'group_id' => $group->id,
            'sender_id' => $user->id,
            'content' => $validated['content'],
            'type' => GroupMessage::TYPE_TEXT,
            'reply_to_message_id' => $validated['reply_to_message_id'] ?? null,
        ]);

        // Mettre à jour le groupe
        $group->updateAfterMessage();

        $message->is_own = true;

        // Diffuser l'événement en temps réel
        broadcast(new GroupMessageSent($message))->toOthers();

        return response()->json([
            'message' => $message,
        ], 201);
    }

    /**
     * Marquer les messages comme lus
     */
    public function markAsRead(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->hasMember($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $member = $group->activeMembers()
            ->where('user_id', $user->id)
            ->first();

        if ($member) {
            $member->updateLastRead();
        }

        return response()->json([
            'message' => 'Messages marqués comme lus.',
        ]);
    }

    /**
     * Liste des membres d'un groupe
     */
    public function members(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->hasMember($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $members = $group->activeMembers()
            ->get()
            ->map(function ($member) use ($user) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'joined_at' => $member->joined_at?->toIso8601String(),
                    'is_muted' => $member->is_muted,
                    'is_self' => $member->user_id === $user->id,
                    // Nom anonyme (ex: Panda56, Licorne42, etc.)
                    'anonymous_name' => $member->anonymous_name,
                    // Pas d'infos personnelles exposées (anonymat complet)
                    'is_identity_revealed' => false, // Premium feature pour révéler l'identité
                ];
            });

        return response()->json([
            'members' => $members,
            'total' => $members->count(),
        ]);
    }

    /**
     * Retirer un membre du groupe (admin/créateur uniquement)
     */
    public function removeMember(Request $request, Group $group, GroupMember $member): JsonResponse
    {
        $user = $request->user();

        // Vérifier les permissions
        if (!$group->isCreator($user) && !$group->isAdmin($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Ne pas retirer le créateur
        if ($member->user_id === $group->creator_id) {
            return response()->json([
                'message' => 'Le créateur ne peut pas être retiré.',
            ], 422);
        }

        // Récupérer le nom anonyme avant de supprimer
        $anonymousName = $member->anonymous_name;
        $member->delete();
        $group->decrement('members_count');

        // Message système avec nom anonyme
        GroupMessage::createSystemMessage($group, "{$anonymousName} a été retiré du groupe");

        return response()->json([
            'message' => 'Membre retiré avec succès.',
        ]);
    }

    /**
     * Changer le rôle d'un membre (créateur uniquement)
     */
    public function updateMemberRole(Request $request, Group $group, GroupMember $member): JsonResponse
    {
        $user = $request->user();

        // Seul le créateur peut changer les rôles
        if (!$group->isCreator($user)) {
            return response()->json([
                'message' => 'Seul le créateur peut modifier les rôles.',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in([
                GroupMember::ROLE_ADMIN,
                GroupMember::ROLE_MODERATOR,
                GroupMember::ROLE_MEMBER,
            ])],
        ]);

        // Ne pas changer le rôle du créateur
        if ($member->user_id === $group->creator_id) {
            return response()->json([
                'message' => 'Le rôle du créateur ne peut pas être modifié.',
            ], 422);
        }

        $member->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'Rôle mis à jour avec succès.',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Régénérer le code d'invitation (créateur/admin uniquement)
     */
    public function regenerateInviteCode(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (!$group->isCreator($user) && !$group->isAdmin($user)) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $group->regenerateInviteCode();

        return response()->json([
            'message' => 'Code d\'invitation régénéré.',
            'invite_code' => $group->invite_code,
            'invite_link' => $group->invite_link,
        ]);
    }

    /**
     * Statistiques des groupes
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $userGroups = Group::whereHas('activeMembers', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });

        $createdGroups = Group::where('creator_id', $user->id);

        return response()->json([
            'total_groups' => $userGroups->count(),
            'created_groups' => $createdGroups->count(),
            'active_groups' => $userGroups->clone()
                ->where('last_message_at', '>=', now()->subDays(7))
                ->count(),
            'total_messages_sent' => GroupMessage::where('sender_id', $user->id)->count(),
        ]);
    }
}
