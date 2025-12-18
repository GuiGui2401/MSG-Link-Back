<?php

use App\Models\Conversation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Canal privé pour les notifications utilisateur
 */
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

/**
 * Canal privé pour une conversation
 */
Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->hasParticipant($user);
});

/**
 * Canal de présence pour voir qui est en ligne
 */
Broadcast::channel('presence.online', function (User $user) {
    return [
        'id' => $user->id,
        'username' => $user->username,
        'initial' => $user->initial,
    ];
});

/**
 * Canal privé pour un groupe
 */
Broadcast::channel('group.{groupId}', function (User $user, int $groupId) {
    $group = Group::find($groupId);

    if (!$group) {
        return false;
    }

    return $group->hasMember($user);
});
