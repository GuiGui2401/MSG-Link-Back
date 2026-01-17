<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MonetizationPayout;
use App\Services\MonetizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonetizationController extends Controller
{
    public function __construct(
        private MonetizationService $monetizationService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $creatorFund = $this->monetizationService->estimateEarnings(
            $user->id,
            MonetizationPayout::TYPE_CREATOR_FUND
        );
        $adRevenue = $this->monetizationService->estimateEarnings(
            $user->id,
            MonetizationPayout::TYPE_AD_REVENUE
        );

        $totals = MonetizationPayout::where('user_id', $user->id)
            ->where('status', MonetizationPayout::STATUS_PAID)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return response()->json([
            'creator_fund' => $creatorFund,
            'ad_revenue' => $adRevenue,
            'totals' => [
                'creator_fund' => (int) ($totals[MonetizationPayout::TYPE_CREATOR_FUND]->total ?? 0),
                'ad_revenue' => (int) ($totals[MonetizationPayout::TYPE_AD_REVENUE]->total ?? 0),
            ],
        ]);
    }

    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();

        $payouts = MonetizationPayout::where('user_id', $user->id)
            ->orderByDesc('period_end')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'payouts' => $payouts->items(),
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    public function settings(): JsonResponse
    {
        return response()->json([
            'enabled' => (bool) setting('monetization_enabled', true),
            'creator_fund_enabled' => (bool) setting('creator_fund_enabled', true),
            'creator_fund_pool_amount' => (int) setting('creator_fund_pool_amount', 0),
            'creator_fund_period_days' => (int) setting('creator_fund_period_days', 30),
            'ads_revenue_enabled' => (bool) setting('ads_revenue_enabled', true),
            'ads_revenue_pool_amount' => (int) setting('ads_revenue_pool_amount', 0),
            'ads_revenue_share_percent' => (float) setting('ads_revenue_share_percent', 50),
            'engagement_view_weight' => (float) setting('engagement_view_weight', 1),
            'engagement_like_weight' => (float) setting('engagement_like_weight', 3),
            'monetization_min_payout' => (int) setting('monetization_min_payout', 100),
        ]);
    }
}
