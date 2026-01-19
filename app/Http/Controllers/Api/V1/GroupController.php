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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            'avatar' => 'nullable|image|max:2048', // 2MB max
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            // Gérer l'avatar si fourni
            $avatarUrl = null;
            if ($request->hasFile('avatar')) {
                $avatarUrl = $request->file('avatar')->store('groups/avatars', 'public');
            }

            // Créer le groupe
            $group = Group::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'creator_id' => $user->id,
                'invite_code' => Group::generateInviteCode(),
                'is_public' => $validated['is_public'] ?? false,
                'max_members' => $validated['max_members'] ?? Group::MAX_MEMBERS_DEFAULT,
                'members_count' => 1,
                'avatar_url' => $avatarUrl,
            ]);

            // Ajouter le créateur comme admin
            $member = $group->members()->create([
                'user_id' => $user->id,
                'role' => GroupMember::ROLE_ADMIN,
                'joined_at' => now(),
            ]);

            // Message système de bienvenue anonyme
            GroupMessage::createSystemMessage($group, "Groupe créé par Anonyme");

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
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($group->avatar_url) {
                Storage::disk('public')->delete($group->avatar_url);
            }
            $validated['avatar_url'] = $request->file('avatar')->store('groups/avatars', 'public');
        }

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
     * Découvrir les groupes publics
     */
    public function discover(Request $request): JsonResponse
    {
        $query = Group::public()
            ->with(['lastMessage']);

        // Filtrage par recherche
        if ($request->has('search')) {
            $query->search($request->get('search'));
        }

        // Tri
        $sortBy = $request->get('sort_by', 'recent');
        if ($sortBy === 'recent') {
            $query->withRecentActivity();
        } elseif ($sortBy === 'members') {
            $query->orderBy('members_count', 'desc');
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', 'asc');
        }

        $groups = $query->paginate($request->get('per_page', 20));

        // Ajouter des infos supplémentaires pour chaque groupe
        $user = $request->user();
        $groups->getCollection()->transform(function ($group) use ($user) {
            $group->is_member = $group->hasMember($user);
            $group->can_join = !$group->is_member && $group->canAcceptMoreMembers();
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
     * Rejoindre un groupe via code d'invitation ou ID (pour les groupes publics)
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invite_code' => 'nullable|string|size:8',
            'group_id' => 'nullable|integer|exists:groups,id',
        ]);

        // On doit avoir soit un code d'invitation, soit un ID de groupe
        if (empty($validated['invite_code']) && empty($validated['group_id'])) {
            return response()->json([
                'message' => 'Vous devez fournir soit un code d\'invitation, soit un ID de groupe.',
            ], 422);
        }

        $user = $request->user();

        // Récupérer le groupe selon le paramètre fourni
        if (!empty($validated['invite_code'])) {
            $group = Group::where('invite_code', $validated['invite_code'])->firstOrFail();
        } else {
            $group = Group::findOrFail($validated['group_id']);

            // Pour rejoindre par ID, le groupe doit être public
            if (!$group->is_public) {
                return response()->json([
                    'message' => 'Ce groupe est privé. Vous devez utiliser un code d\'invitation.',
                ], 403);
            }
        }

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

        // Recharger le groupe avec les relations et informations nécessaires
        $group->load(['lastMessage']);
        $group->unread_count = $group->unreadCountFor($user);
        $group->is_creator = $group->isCreator($user);
        $group->is_admin = $group->isAdmin($user);

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

        $canSeeIdentity = $user->is_premium ?? false;

        // Charger la relation sender si premium + replyToMessage
        $query = $group->messages()->orderBy('created_at', 'desc');
        if ($canSeeIdentity) {
            $query->with(['sender', 'replyToMessage']);
        } else {
            $query->with('replyToMessage');
        }

        $messages = $query->paginate($request->get('per_page', 50));

        // Ajouter l'info si c'est le message de l'utilisateur et les données du sender
        $messages->getCollection()->transform(function ($message) use ($user, $canSeeIdentity) {
            $message->is_own = $message->sender_id === $user->id;

            // Ajouter les données du sender selon le statut premium
            if ($canSeeIdentity && $message->sender) {
                $message->sender_first_name = $message->sender->first_name;
                $message->sender_last_name = $message->sender->last_name;
                $message->sender_username = $message->sender->username;
                $message->sender_avatar_url = $message->sender->avatar_url;
                $message->sender_initial = $message->sender->initial;
            } else {
                $message->sender_name = 'Anonyme';
                $message->sender_initial = 'A';
            }

            // Ajouter les données du message auquel on répond
            if ($message->replyToMessage) {
                $message->reply_to_message = [
                    'id' => $message->replyToMessage->id,
                    'content' => $message->replyToMessage->content,
                    'type' => $message->replyToMessage->type,
                    'sender_anonymous_name' => $message->replyToMessage->sender_anonymous_name,
                ];
            }

            return $message;
        });

        Log::info('[GroupMessages] list', [
            'group_id' => $group->id,
            'host' => $request->getSchemeAndHttpHost(),
            'sample_media_url' => $messages->items()[0]->media_url ?? null,
            'sample_media_full_url' => $messages->items()[0]->media_full_url ?? null,
        ]);

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
            'content' => 'nullable|string|max:5000',
            'reply_to_message_id' => 'nullable|exists:group_messages,id',
            'voice_effect' => 'nullable|string|max:50',
            'voice' => 'nullable|file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/wave,audio/vnd.wave,audio/m4a,audio/x-m4a,audio/mp4,audio/aac,audio/x-aac,audio/ogg,audio/webm|max:10240',
            'video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska|max:51200',
            'image' => 'nullable|image|max:10240',
        ]);

        // Vérifier qu'au moins un contenu est fourni
        if (empty($validated['content']) && !$request->hasFile('voice') && !$request->hasFile('video') && !$request->hasFile('image')) {
            return response()->json([
                'message' => 'Veuillez fournir un message, un audio, une vidéo ou une image.',
                'errors' => ['content' => ['Le contenu est requis si aucun média n\'est fourni.']],
            ], 422);
        }

        $messageData = [
            'group_id' => $group->id,
            'sender_id' => $user->id,
            'content' => $validated['content'] ?? '',
            'type' => GroupMessage::TYPE_TEXT,
            'reply_to_message_id' => $validated['reply_to_message_id'] ?? null,
        ];

        // Gérer l'upload de fichier vocal
        if ($request->hasFile('voice')) {
            $voice = $request->file('voice');
            $path = $voice->store('group_messages/voices', 'public');
            $messageData['media_url'] = $path;
            $messageData['type'] = GroupMessage::TYPE_VOICE;
            $messageData['voice_effect'] = $validated['voice_effect'] ?? null;
            Log::info('[GroupMessage] voice uploaded', [
                'group_id' => $group->id,
                'path' => $path,
                'exists' => Storage::disk('public')->exists($path),
            ]);
        }

        // Gérer l'upload de vidéo
        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $path = $video->store('group_messages/videos', 'public');
            $messageData['media_url'] = $path;
            $messageData['type'] = GroupMessage::TYPE_VIDEO;
            Log::info('[GroupMessage] video uploaded', [
                'group_id' => $group->id,
                'path' => $path,
                'exists' => Storage::disk('public')->exists($path),
            ]);
        }

        // Gérer l'upload d'image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('group_messages/images', 'public');
            $messageData['media_url'] = $path;
            $messageData['type'] = GroupMessage::TYPE_IMAGE;
            Log::info('[GroupMessage] image uploaded', [
                'group_id' => $group->id,
                'path' => $path,
                'exists' => Storage::disk('public')->exists($path),
            ]);
        }

        // Créer le message
        $message = GroupMessage::create($messageData);

        // Mettre à jour le groupe
        $group->updateAfterMessage();

        $message->is_own = true;

        // Ajouter les données du sender pour le retour API
        $canSeeIdentity = $user->is_premium ?? false;
        if ($canSeeIdentity) {
            $message->sender_first_name = $user->first_name;
            $message->sender_last_name = $user->last_name;
            $message->sender_username = $user->username;
            $message->sender_avatar_url = $user->avatar_url;
            $message->sender_initial = $user->initial;
        } else {
            $message->sender_name = 'Anonyme';
            $message->sender_initial = 'A';
        }

        // Diffuser l'événement en temps réel
        try {
            broadcast(new GroupMessageSent($message))->toOthers();
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas l'envoi du message
            \Log::warning('Broadcasting failed for group message: ' . $e->getMessage());
        }

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

        // Charger la relation user pour révéler l'identité si premium
        $members = $group->activeMembers()
            ->with('user')
            ->get()
            ->map(function ($member) use ($user) {
                $canSeeIdentity = $user->is_premium ?? false;

                $memberData = [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'joined_at' => $member->joined_at?->toIso8601String(),
                    'is_muted' => $member->is_muted,
                    'is_self' => $member->user_id === $user->id,
                    'is_identity_revealed' => $canSeeIdentity,
                ];

                // Si premium, révéler l'identité
                if ($canSeeIdentity && $member->user) {
                    $memberData['first_name'] = $member->user->first_name;
                    $memberData['last_name'] = $member->user->last_name;
                    $memberData['username'] = $member->user->username;
                    $memberData['avatar_url'] = $member->user->avatar_url;
                    $memberData['initial'] = $member->user->initial;
                } else {
                    // Sinon, anonyme
                    $memberData['display_name'] = 'Anonyme';
                    $memberData['initial'] = 'A';
                }

                return $memberData;
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
