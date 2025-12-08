<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        if (!$user->is_admin && !$user->is_moderator) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Les modérateurs ont accès limité
        if ($user->is_moderator && !$user->is_admin) {
            $allowedRoutes = [
                'admin/moderation/*',
                'admin/dashboard',
            ];

            $currentRoute = $request->path();
            $isAllowed = false;

            foreach ($allowedRoutes as $pattern) {
                if (fnmatch($pattern, $currentRoute)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                return response()->json([
                    'message' => 'Accès réservé aux administrateurs.',
                ], 403);
            }
        }

        return $next($request);
    }
}
