<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log de la requête entrante
        Log::info('📨 [API_REQUEST] ==================== NOUVELLE REQUÊTE ====================');
        Log::info('📨 [API_REQUEST] Méthode: ' . $request->method());
        Log::info('📨 [API_REQUEST] URL: ' . $request->fullUrl());
        Log::info('📨 [API_REQUEST] Path: ' . $request->path());
        Log::info('📨 [API_REQUEST] IP: ' . $request->ip());
        Log::info('📨 [API_REQUEST] User Agent: ' . $request->userAgent());

        // Headers
        Log::info('📨 [API_REQUEST] Headers:', [
            'Authorization' => $request->header('Authorization') ? 'Bearer ' . substr($request->header('Authorization'), 7, 20) . '...' : 'None',
            'Content-Type' => $request->header('Content-Type'),
            'Accept' => $request->header('Accept'),
            'Origin' => $request->header('Origin'),
            'Referer' => $request->header('Referer'),
        ]);

        // Body (sauf les mots de passe)
        $body = $request->except(['password', 'password_confirmation', 'pin']);
        Log::info('📨 [API_REQUEST] Body:', $body);

        // Query params
        if ($request->query()) {
            Log::info('📨 [API_REQUEST] Query params:', $request->query());
        }

        // User authentifié
        if ($request->user()) {
            Log::info('📨 [API_REQUEST] User authentifié: ' . $request->user()->username . ' (ID: ' . $request->user()->id . ')');
        } else {
            Log::info('📨 [API_REQUEST] User: Non authentifié');
        }

        // Timestamp de début
        $start = microtime(true);

        // Continuer avec la requête
        $response = $next($request);

        // Temps d'exécution
        $duration = round((microtime(true) - $start) * 1000, 2);

        // Log de la réponse
        Log::info('📤 [API_RESPONSE] ==================== RÉPONSE ====================');
        Log::info('📤 [API_RESPONSE] Status: ' . $response->status());
        Log::info('📤 [API_RESPONSE] Durée: ' . $duration . 'ms');

        // Contenu de la réponse (seulement pour JSON)
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if (isset($content['token'])) {
                $content['token'] = substr($content['token'], 0, 20) . '...';
            }
            Log::info('📤 [API_RESPONSE] Contenu:', $content);
        }

        Log::info('📤 [API_RESPONSE] ==================== FIN REQUÊTE ====================');

        return $response;
    }
}
