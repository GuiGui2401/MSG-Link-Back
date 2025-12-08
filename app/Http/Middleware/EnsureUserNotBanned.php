<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBanned
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            // Révoquer le token actuel
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Votre compte a été suspendu.',
                'reason' => $user->banned_reason,
                'banned_at' => $user->banned_at?->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}
