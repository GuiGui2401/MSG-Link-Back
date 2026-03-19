<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     *
     * Essaie d'authentifier l'utilisateur avec Sanctum mais ne fail pas si pas de token.
     * Cela permet aux routes publiques de bénéficier de l'authentification optionnelle.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Essayer d'authentifier avec Sanctum si un token est présent
        if ($request->bearerToken()) {
            try {
                // Forcer la résolution de l'utilisateur à partir du token
                $user = Auth::guard('sanctum')->user();

                if ($user) {
                    // Authentifier l'utilisateur dans la requête
                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs d'authentification - on permet l'accès public
                \Log::debug('Optional auth failed, continuing as guest', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $next($request);
    }
}
