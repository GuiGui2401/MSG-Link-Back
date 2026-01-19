<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    /**
     * Follow a user
     */
    public function follow(Request $request, string $username): JsonResponse
    {
        $user = $this->resolveUser($request, $username);
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous suivre vous-même',
            ], 400);
        }

        if ($currentUser->hasBlocked($user) || $user->hasBlocked($currentUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Action impossible',
            ], 400);
        }

        $followed = $currentUser->follow($user);

        return response()->json([
            'success' => true,
            'message' => $followed ? 'Vous suivez maintenant ' . $user->username : 'Vous suivez déjà cet utilisateur',
            'is_following' => true,
            'followers_count' => $user->followers_count,
        ]);
    }

    /**
     * Unfollow a user
     */
    public function unfollow(Request $request, string $username): JsonResponse
    {
        $user = $this->resolveUser($request, $username);
        $currentUser = $request->user();

        $unfollowed = $currentUser->unfollow($user);

        return response()->json([
            'success' => true,
            'message' => $unfollowed ? 'Vous ne suivez plus ' . $user->username : 'Vous ne suiviez pas cet utilisateur',
            'is_following' => false,
            'followers_count' => $user->followers_count,
        ]);
    }

    /**
     * Get followers list
     */
    public function followers(Request $request, string $username): JsonResponse
    {
        $user = $this->resolveUser($request, $username);
        $currentUser = $request->user();

        $followers = $user->followers()
            ->select('users.id', 'users.username', 'users.first_name', 'users.last_name', 'users.avatar', 'users.bio', 'users.is_premium')
            ->paginate(20);

        $followers->getCollection()->transform(function ($follower) use ($currentUser) {
            $follower->is_following = $currentUser ? $currentUser->isFollowing($follower) : false;
            $follower->avatar_url = $follower->avatar_url;
            return $follower;
        });

        return response()->json([
            'success' => true,
            'data' => $followers,
        ]);
    }

    /**
     * Get following list
     */
    public function following(Request $request, string $username): JsonResponse
    {
        $user = $this->resolveUser($request, $username);
        $currentUser = $request->user();

        $following = $user->following()
            ->select('users.id', 'users.username', 'users.first_name', 'users.last_name', 'users.avatar', 'users.bio', 'users.is_premium')
            ->paginate(20);

        $following->getCollection()->transform(function ($followedUser) use ($currentUser) {
            $followedUser->is_following = $currentUser ? $currentUser->isFollowing($followedUser) : false;
            $followedUser->avatar_url = $followedUser->avatar_url;
            return $followedUser;
        });

        return response()->json([
            'success' => true,
            'data' => $following,
        ]);
    }

    /**
     * Check if following a user
     */
    public function checkFollowing(Request $request, string $username): JsonResponse
    {
        $user = $this->resolveUser($request, $username);
        $currentUser = $request->user();

        return response()->json([
            'success' => true,
            'is_following' => $currentUser->isFollowing($user),
            'is_followed_by' => $user->isFollowing($currentUser),
        ]);
    }

    private function resolveUser(Request $request, string $identifier): User
    {
        $normalized = trim($identifier);
        $query = User::withTrashed();

        $user = $query->where('username', $normalized)->first();
        if (!$user) {
            $user = $query
                ->whereRaw('LOWER(TRIM(username)) = ?', [strtolower($normalized)])
                ->first();
        }
        if (!$user) {
            $user = $query->where('email', $normalized)->first();
        }
        if (!$user) {
            $user = $query->where('phone', $normalized)->first();
        }
        if (!$user && ctype_digit($normalized)) {
            $user = $query->find((int) $normalized);
        }
        if (!$user && $request->user()) {
            $authUser = $request->user();
            if (
                ($authUser->username && strtolower($authUser->username) === strtolower($normalized)) ||
                (string) $authUser->id === $normalized
            ) {
                $user = $authUser;
            }
        }

        if (!$user) {
            abort(404, 'Utilisateur non trouvé.');
        }

        return $user;
    }
}
