<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Get public settings
     */
    public function getPublicSettings(): JsonResponse
    {
        return response()->json([
            'reveal_anonymous_price' => reveal_anonymous_price(),
            'premium_monthly_price' => setting('premium_monthly_price', 450),
            'premium_enabled' => is_premium_enabled(),
            'currency' => 'FCFA',
        ]);
    }

    /**
     * Get reveal anonymous price
     */
    public function getRevealPrice(): JsonResponse
    {
        return response()->json([
            'price' => reveal_anonymous_price(),
            'currency' => 'FCFA',
        ]);
    }
}
