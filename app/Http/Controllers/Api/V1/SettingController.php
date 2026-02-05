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
            // Premium & Reveal
            'reveal_anonymous_price' => (int) reveal_anonymous_price(),
            'premium_monthly_price' => (int) setting('premium_monthly_price', 450),
            'premium_enabled' => is_premium_enabled(),

            // Groups
            'group_max_members_default' => (int) setting('group_max_members_default', 50),
            'group_max_members_premium' => (int) setting('group_max_members_premium', 200),
            'group_max_per_user' => (int) setting('group_max_per_user', 10),

            // Wallet
            'deposit_min_amount' => (int) setting('deposit_min_amount', 500),
            'wallet_min_withdrawal' => (int) setting('wallet_min_withdrawal', 1000),
            'wallet_withdrawal_fee' => (int) setting('wallet_withdrawal_fee', 0),

            // Promotions
            'promotion_min_budget' => (int) setting('promotion_min_budget', 1000),
            'promotion_allow_images' => (bool) setting('promotion_allow_images', true),

            // Currency
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
