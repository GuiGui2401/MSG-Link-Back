<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Confession;
use App\Models\PostPromotion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            'duration_hours' => 'required|in:24,72,168',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentUser = $request->user();

        // Check if user owns the confession
        if ($confession->author_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez promouvoir que vos propres publications',
            ], 403);
        }

        // Check if already promoted
        $existingPromotion = $confession->activePromotion();
        if ($existingPromotion) {
            return response()->json([
                'success' => false,
                'message' => 'Cette publication est déjà en promotion',
                'data' => $existingPromotion,
            ], 400);
        }

        // Get pricing
        $pricing = [
            24 => ['price' => 500, 'boost' => 100],
            72 => ['price' => 1200, 'boost' => 150],
            168 => ['price' => 2500, 'boost' => 200],
        ];

        $durationHours = (int) $request->duration_hours;
        $price = $pricing[$durationHours]['price'];
        $boost = $pricing[$durationHours]['boost'];

        // Check wallet balance
        if ($currentUser->wallet_balance < $price) {
            return response()->json([
                'success' => false,
                'message' => 'Solde insuffisant',
                'required' => $price,
                'balance' => $currentUser->wallet_balance,
            ], 400);
        }

        // Deduct from wallet
        $currentUser->decrement('wallet_balance', $price);

        // Create promotion
        $promotion = PostPromotion::create([
            'confession_id' => $confession->id,
            'user_id' => $currentUser->id,
            'amount' => $price,
            'duration_hours' => $durationHours,
            'reach_boost' => $boost,
            'starts_at' => now(),
            'ends_at' => now()->addHours($durationHours),
            'status' => PostPromotion::STATUS_ACTIVE,
        ]);

        // Record transaction
        $currentUser->walletTransactions()->create([
            'type' => 'debit',
            'amount' => $price,
            'description' => 'Promotion de publication',
            'reference' => 'promo_' . $promotion->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Publication promue avec succès',
            'data' => $promotion,
        ], 201);
    }

    /**
     * Get user's promotions
     */
    public function myPromotions(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $promotions = PostPromotion::where('user_id', $currentUser->id)
            ->with(['confession:id,content,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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
                'time_remaining' => $promotion->ends_at->diffForHumans(),
                'is_active' => $promotion->isActive(),
            ],
        ]);
    }
}
