<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if maintenance mode is enabled
        $isMaintenanceMode = Setting::get('maintenance_mode_enabled', false);

        if ($isMaintenanceMode) {
            // Allow admins to bypass maintenance mode
            $user = $request->user();
            if ($user && in_array($user->role, ['admin', 'superadmin', 'moderator'])) {
                return $next($request);
            }

            // Return maintenance mode response
            $message = Setting::get('maintenance_mode_message', 'Le site est actuellement en maintenance. Nous reviendrons bientôt !');
            $estimatedEndTime = Setting::get('maintenance_mode_end_time', null);

            return response()->json([
                'success' => false,
                'maintenance_mode' => true,
                'message' => $message,
                'estimated_end_time' => $estimatedEndTime,
            ], 503);
        }

        return $next($request);
    }
}
