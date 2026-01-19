<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Confession;
use App\Models\PostPromotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PostPromotionController extends Controller
{
    /**
     * Get promotion pricing
     */
    public function pricing(): JsonResponse
    {
        $pricing = [
            [
                'duration_hours' => 24,
                'price' => 500,
                'reach_boost' => 100,
                'label' => '24 heures',
            ],
            [
                'duration_hours' => 72,
                'price' => 1200,
                'reach_boost' => 150,
                'label' => '3 jours',
            ],
            [
                'duration_hours' => 168,
                'price' => 2500,
                'reach_boost' => 200,
                'label' => '1 semaine',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * Promote a post
     */
    public function promote(Request $request, Confession $confession): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'duration_hours' => 'required_without:duration_days|in:24,72,168',
            'duration_days' => 'nullable|integer|min:1|max:7|required_with:budget_mode',
            'goal' => 'nullable|in:video_views,profile_views,followers,messages,website,conversions',
            'sub_goal' => 'nullable|string|max:120',
            'audience_mode' => 'nullable|in:auto,custom',
            'gender' => 'nullable|in:Tous,Homme,Femme',
            'age_range' => 'nullable|string|max:20',
            'locations' => 'nullable|array',
            'interests' => 'nullable|array',
            'language' => 'nullable|string|max:30',
            'device_type' => 'nullable|in:Tous,Android,iOS',
            'budget_mode' => 'nullable|in:daily,total',
            'daily_budget' => 'nullable|numeric|min:1000|required_if:budget_mode,daily',
            'total_budget' => 'nullable|numeric|min:1000|required_if:budget_mode,total',
            'cta_label' => 'nullable|string|max:40',
            'website_url' => 'required_if:goal,website|nullable|url|max:255',
            'branded_content' => 'nullable|boolean',
            'payment_method' => 'nullable|in:wallet,card,promo_balance',
            'confession_ids' => 'nullable|array|max:5',
            'confession_ids.*' => 'integer',
            'estimated_views' => 'nullable|numeric|min:0',
            'estimated_reach' => 'nullable|numeric|min:0',
            'estimated_cpv' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentUser = $request->user();

        $confessionIds = $request->input('confession_ids');
        $confessions = collect([$confession]);

        if (is_array($confessionIds) && count($confessionIds) > 0) {
            $confessions = Confession::whereIn('id', $confessionIds)->get();
        }

        if ($confessions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune publication valide à promouvoir',
            ], 422);
        }

        $confessions = $confessions->filter(function (Confession $item) use ($currentUser) {
            return $item->author_id === $currentUser->id && $item->is_public && $item->is_approved;
        });

        if ($confessions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez promouvoir que vos publications publiques approuvées',
            ], 403);
        }

        $alreadyPromoted = $confessions->filter(fn (Confession $item) => $item->activePromotion() !== null);
        if ($alreadyPromoted->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Une ou plusieurs publications sont déjà en promotion',
                'data' => $alreadyPromoted->pluck('id'),
            ], 400);
        }

        $durationHours = (int) ($request->duration_hours ?? 0);
        $durationDays = $request->input('duration_days');
        if ($durationDays) {
            $durationHours = (int) $durationDays * 24;
        }

        $pricing = [
            24 => ['price' => 500, 'boost' => 100],
            72 => ['price' => 1200, 'boost' => 150],
            168 => ['price' => 2500, 'boost' => 200],
        ];

        $price = $durationHours && isset($pricing[$durationHours])
            ? $pricing[$durationHours]['price']
            : 0;
        $boost = $durationHours && isset($pricing[$durationHours])
            ? $pricing[$durationHours]['boost']
            : ($durationHours >= 120 ? 200 : ($durationHours >= 72 ? 150 : 100));

        $budgetMode = $request->input('budget_mode');
        if ($budgetMode === 'daily') {
            $dailyBudget = (float) $request->input('daily_budget', 0);
            $price = $dailyBudget * (int) $durationDays;
        } elseif ($budgetMode === 'total') {
            $price = (float) $request->input('total_budget', 0);
        }

        $paymentMethod = $request->input('payment_method', 'wallet');

        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Budget invalide',
            ], 422);
        }

        if ($confessions->count() > 1 && $budgetMode === null) {
            $price = $price * $confessions->count();
        }

        $walletBalanceBefore = $currentUser->wallet_balance;
        $walletBalanceAfter = $walletBalanceBefore;

        if ($paymentMethod === 'promo_balance') {
            if ($currentUser->promotion_balance < $price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde promotionnel insuffisant',
                    'required' => $price,
                    'balance' => $currentUser->promotion_balance,
                ], 400);
            }
            $currentUser->decrement('promotion_balance', $price);
        } else {
            if ($currentUser->wallet_balance < $price) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant',
                    'required' => $price,
                    'balance' => $currentUser->wallet_balance,
                ], 400);
            }
            $currentUser->decrement('wallet_balance', $price);
            $walletBalanceAfter = $currentUser->wallet_balance;
        }

        $campaignId = (string) Str::uuid();
        $perPostAmount = $price / max(1, $confessions->count());
        $perPostTotalBudget = $budgetMode === 'total' ? $perPostAmount : null;

        $promotions = $confessions->map(function (Confession $item) use (
            $currentUser,
            $perPostAmount,
            $durationHours,
            $boost,
            $request,
            $budgetMode,
            $durationDays,
            $paymentMethod,
            $campaignId,
            $perPostTotalBudget
        ) {
            return PostPromotion::create([
                'confession_id' => $item->id,
                'user_id' => $currentUser->id,
                'amount' => $perPostAmount,
                'duration_hours' => $durationHours,
                'reach_boost' => $boost,
                'goal' => $request->input('goal'),
                'sub_goal' => $request->input('sub_goal'),
                'audience_mode' => $request->input('audience_mode', 'auto'),
                'gender' => $request->input('gender'),
                'age_range' => $request->input('age_range'),
                'locations' => $request->input('locations'),
                'interests' => $request->input('interests'),
                'language' => $request->input('language'),
                'device_type' => $request->input('device_type'),
                'budget_mode' => $budgetMode,
                'daily_budget' => $budgetMode === 'daily' ? $request->input('daily_budget') : null,
                'total_budget' => $budgetMode === 'total' ? $perPostTotalBudget : $perPostAmount,
                'duration_days' => $durationDays,
                'cta_label' => $request->input('cta_label'),
                'website_url' => $request->input('website_url'),
                'branded_content' => (bool) $request->input('branded_content', false),
                'payment_method' => $paymentMethod,
                'estimated_views' => $request->input('estimated_views'),
                'estimated_reach' => $request->input('estimated_reach'),
                'estimated_cpv' => $request->input('estimated_cpv'),
                'campaign_id' => $campaignId,
                'starts_at' => now(),
                'ends_at' => now()->addHours($durationHours),
                'status' => PostPromotion::STATUS_ACTIVE,
            ]);
        });

        // Record transaction
        $currentUser->walletTransactions()->create([
            'type' => $paymentMethod === 'promo_balance' ? 'debit' : 'debit',
            'amount' => $price,
            'description' => $paymentMethod === 'promo_balance'
                ? 'Promotion (solde promo)'
                : 'Promotion de publication',
            'reference' => 'promo_' . $campaignId,
            'balance_before' => $walletBalanceBefore,
            'balance_after' => $walletBalanceAfter,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Publication promue avec succès',
            'data' => $promotions,
            'campaign_id' => $campaignId,
        ], 201);
    }

    public function balance(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'promotion_balance' => $currentUser->wallet_balance ?? 0,
                'wallet_balance' => $currentUser->wallet_balance ?? 0,
            ],
        ]);
    }

    public function topup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000',
            'method' => 'required|in:wallet,card',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentUser = $request->user();
        $amount = (float) $request->amount;

        $walletBalanceBefore = $currentUser->wallet_balance;
        $walletBalanceAfter = $walletBalanceBefore;

        if ($request->method === 'wallet') {
            if ($currentUser->wallet_balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant',
                    'required' => $amount,
                    'balance' => $currentUser->wallet_balance,
                ], 400);
            }
            $currentUser->decrement('wallet_balance', $amount);
            $walletBalanceAfter = $currentUser->wallet_balance;
        }

        $currentUser->increment('promotion_balance', $amount);

        $currentUser->walletTransactions()->create([
            'type' => $request->method === 'wallet' ? 'debit' : 'credit',
            'amount' => $amount,
            'description' => $request->method === 'wallet'
                ? 'Recharge solde promotionnel'
                : 'Recharge solde promotionnel (carte)',
            'reference' => 'promo_topup_' . Str::uuid(),
            'balance_before' => $walletBalanceBefore,
            'balance_after' => $walletBalanceAfter,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solde promotionnel rechargé',
            'data' => [
                'promotion_balance' => $currentUser->promotion_balance,
                'wallet_balance' => $currentUser->wallet_balance,
            ],
        ]);
    }

    /**
     * Get user's promotions
     */
    public function myPromotions(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $promotions = PostPromotion::where('user_id', $currentUser->id)
            ->with(['confession:id,content,image,video,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $promotions->getCollection()->transform(function (PostPromotion $promotion) {
            if ($promotion->confession) {
                $promotion->confession->setAttribute('image_url', $promotion->confession->image_url);
                $promotion->confession->setAttribute('video_url', $promotion->confession->video_url);
            }
            return $promotion;
        });

        return response()->json([
            'success' => true,
            'data' => $promotions,
        ]);
    }

    /**
     * Cancel a promotion
     */
    public function cancel(Request $request, PostPromotion $promotion): JsonResponse
    {
        $currentUser = $request->user();

        if ($promotion->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        if ($promotion->status !== PostPromotion::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Cette promotion ne peut pas être annulée',
            ], 400);
        }

        $promotion->update(['status' => PostPromotion::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => 'Promotion annulée',
        ]);
    }

    /**
     * Get promotion stats
     */
    public function stats(Request $request, PostPromotion $promotion): JsonResponse
    {
        $currentUser = $request->user();

        if ($promotion->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'impressions' => $promotion->impressions,
                'clicks' => $promotion->clicks,
                'ctr' => $promotion->impressions > 0
                    ? round(($promotion->clicks / $promotion->impressions) * 100, 2)
                    : 0,
                'budget_spent' => $promotion->amount,
                'goal' => $promotion->goal,
                'estimated_views' => $promotion->estimated_views,
                'estimated_reach' => $promotion->estimated_reach,
                'time_remaining' => $promotion->ends_at->diffForHumans(),
                'is_active' => $promotion->isActive(),
            ],
        ]);
    }
}
