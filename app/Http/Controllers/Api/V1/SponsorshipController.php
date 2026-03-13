<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SponsorshipResource;
use App\Models\Sponsorship;
use App\Models\SponsorshipImpression;
use App\Models\SponsorshipPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SponsorshipController extends Controller
{
    /**
     * Ads feed pour l'utilisateur courant (sponsoring actifs, non expirés).
     *
     * Note: on n'exclut pas un sponsoring déjà vu par le user, car:
     * - le comptage "users touchés" est géré par impressions uniques (1 user = 1 impression)
     * - l'ad peut réapparaître "de temps en temps" pendant le scroll, sans surcomptage
     * - le sponsor doit aussi pouvoir voir son propre ad
     *
     * GET /api/v1/sponsorships/feed?limit=10
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->get('limit', 10);
        $limit = max(1, min($limit, 20));

        $now = now();

        $baseQuery = Sponsorship::query()
            ->with('user:id,first_name,last_name,username,avatar,last_seen_at')
            ->where('status', Sponsorship::STATUS_ACTIVE)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->whereRaw('delivered_count < COALESCE(reach_max, reach_min)');

        // 1) Toujours inclure les ads du sponsor lui-même (si existantes)
        $ownAdsLimit = min(3, $limit);
        $ownAds = (clone $baseQuery)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($ownAdsLimit)
            ->get();

        // 2) Compléter avec des ads d'autres sponsors (random)
        $remaining = $limit - $ownAds->count();
        $otherAds = collect();
        if ($remaining > 0) {
            $otherAds = (clone $baseQuery)
                ->where('user_id', '!=', $user->id)
                ->inRandomOrder()
                ->limit($remaining)
                ->get();
        }

        $ads = $ownAds->concat($otherAds)->values();

        return response()->json([
            'ads' => SponsorshipResource::collection($ads),
        ]);
    }

    /**
     * Retourner les sponsorings de l'utilisateur courant (pour dashboard).
     *
     * GET /api/v1/sponsorships/mine?limit=50
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->get('limit', 50);
        $limit = max(1, min($limit, 100));

        $sponsorships = Sponsorship::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'sponsorships' => SponsorshipResource::collection($sponsorships),
        ]);
    }

    /**
     * Statistiques du dashboard sponsoring.
     *
     * GET /api/v1/sponsorships/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();

        $base = Sponsorship::query()->where('user_id', $user->id);

        $totalTarget = (int) (clone $base)
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(reach_max, 0), reach_min)), 0) as total_target')
            ->value('total_target');

        $stats = [
            'total_count' => (int) (clone $base)->count(),
            'active_count' => (int) (clone $base)->where('status', Sponsorship::STATUS_ACTIVE)->count(),
            'paused_count' => (int) (clone $base)->where('status', Sponsorship::STATUS_PAUSED)->count(),
            'completed_count' => (int) (clone $base)->where('status', Sponsorship::STATUS_COMPLETED)->count(),
            'cancelled_count' => (int) (clone $base)->where('status', Sponsorship::STATUS_CANCELLED)->count(),
            'expired_count' => (int) (clone $base)->whereNotNull('ends_at')->where('ends_at', '<=', $now)->count(),
            'total_delivered' => (int) (clone $base)->sum('delivered_count'),
            'total_target' => $totalTarget,
        ];

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Marquer une impression (1 user compte 1 fois) et incrémenter delivered_count.
     *
     * POST /api/v1/sponsorships/{sponsorship}/impression
     */
    public function impression(Request $request, Sponsorship $sponsorship): JsonResponse
    {
        $user = $request->user();
        $now = now();

        // Le sponsor doit pouvoir voir son ad, mais ça ne doit pas consommer la portée.
        if ($user->id === $sponsorship->user_id) {
            return response()->json([
                'message' => 'Impression ignorée (owner).',
                'sponsorship' => new SponsorshipResource($sponsorship),
            ]);
        }

        if ($sponsorship->status !== Sponsorship::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'Sponsoring non disponible.',
            ], 410);
        }

        if ($sponsorship->ends_at && $sponsorship->ends_at->lte($now)) {
            $sponsorship->update(['status' => Sponsorship::STATUS_COMPLETED]);
            return response()->json([
                'message' => 'Sponsoring expiré.',
                'sponsorship' => new SponsorshipResource($sponsorship->fresh()),
            ], 410);
        }

        $target = $sponsorship->target_reach;
        if ($sponsorship->delivered_count >= $target) {
            $sponsorship->update(['status' => Sponsorship::STATUS_COMPLETED]);
            return response()->json([
                'message' => 'Objectif déjà atteint.',
                'sponsorship' => new SponsorshipResource($sponsorship->fresh()),
            ], 410);
        }

        try {
            $result = DB::transaction(function () use ($sponsorship, $user, $target) {
                $impression = SponsorshipImpression::firstOrCreate([
                    'sponsorship_id' => $sponsorship->id,
                    'viewer_id' => $user->id,
                ]);

                if ($impression->wasRecentlyCreated) {
                    $sponsorship->increment('delivered_count');
                }

                $sponsorship = $sponsorship->fresh();

                if ($sponsorship->delivered_count >= $target) {
                    $sponsorship->update(['status' => Sponsorship::STATUS_COMPLETED]);
                    $sponsorship = $sponsorship->fresh();
                }

                return [
                    'created' => $impression->wasRecentlyCreated,
                    'sponsorship' => $sponsorship,
                ];
            });

            return response()->json([
                'message' => $result['created'] ? 'Impression enregistrée.' : 'Impression déjà comptabilisée.',
                'sponsorship' => new SponsorshipResource($result['sponsorship']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement de l\'impression: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Acheter un sponsoring (paiement par wallet)
     *
     * POST /api/v1/sponsorships/purchase
     * - package_id (required)
     * - media_type: text|image|video (required)
     * - content (required si media_type=text)
     * - media (required si media_type=image|video) (multipart)
     */
    public function purchase(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'package_id' => 'required|integer|exists:sponsorship_packages,id',
            'media_type' => 'required|string|in:text,image,video',
            'content' => 'nullable|string|max:5000',
            'media' => 'nullable|file|max:102400', // 100MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $package = SponsorshipPackage::query()
            ->active()
            ->where('id', $validated['package_id'])
            ->first();

        if (!$package) {
            return response()->json([
                'message' => 'Package sponsoring non disponible.',
            ], 404);
        }

        // Conditions media
        $mediaType = $validated['media_type'];
        $hasMediaFile = $request->hasFile('media');
        $hasText = !empty($validated['content']);

        if ($mediaType === Sponsorship::MEDIA_TEXT && !$hasText) {
            return response()->json([
                'message' => 'Vous devez fournir un texte à sponsoriser.',
            ], 422);
        }

        if (in_array($mediaType, [Sponsorship::MEDIA_IMAGE, Sponsorship::MEDIA_VIDEO], true) && !$hasMediaFile) {
            return response()->json([
                'message' => 'Vous devez fournir un fichier média (image ou vidéo).',
            ], 422);
        }

        // Vérifier le solde
        if (!$user->hasEnoughBalance($package->price)) {
            return response()->json([
                'message' => 'Solde insuffisant. Veuillez recharger votre wallet.',
                'required_amount' => $package->price,
                'current_balance' => $user->wallet_balance,
            ], 422);
        }

        $mediaPath = null;
        if ($hasMediaFile) {
            $media = $request->file('media');

            // Valider le type MIME selon le type
            if ($mediaType === Sponsorship::MEDIA_IMAGE) {
                if (!in_array($media->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                    return response()->json([
                        'message' => 'Le fichier doit être une image (JPEG, PNG, GIF, WebP).',
                    ], 422);
                }
            } elseif ($mediaType === Sponsorship::MEDIA_VIDEO) {
                if (!in_array($media->getMimeType(), ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'])) {
                    return response()->json([
                        'message' => 'Le fichier doit être une vidéo (MP4, MOV, AVI, WebM).',
                    ], 422);
                }
            }

            $mediaPath = $media->store('sponsoring/' . $user->id, 'public');
        }

        try {
            $sponsorship = DB::transaction(function () use ($user, $package, $mediaType, $mediaPath, $validated) {
                $sponsorship = Sponsorship::create([
                    'user_id' => $user->id,
                    'sponsorship_package_id' => $package->id,
                    'media_type' => $mediaType,
                    'text_content' => $mediaType === Sponsorship::MEDIA_TEXT ? ($validated['content'] ?? null) : null,
                    'media_url' => $mediaType !== Sponsorship::MEDIA_TEXT ? $mediaPath : null,
                    'price' => $package->price,
                    'reach_min' => $package->reach_min,
                    'reach_max' => $package->reach_max,
                    'duration_days' => $package->duration_days,
                    'ends_at' => now()->addDays($package->duration_days),
                    'status' => Sponsorship::STATUS_ACTIVE,
                    'delivered_count' => 0,
                ]);

                // Débiter le wallet (en liant la transaction au sponsoring)
                $user->debitWallet(
                    $package->price,
                    "Sponsoring : {$package->name}",
                    $sponsorship
                );

                return $sponsorship->fresh();
            });

            return response()->json([
                'message' => 'Sponsoring acheté avec succès.',
                'sponsorship' => new SponsorshipResource($sponsorship),
                'wallet' => [
                    'balance' => $user->fresh()->wallet_balance,
                    'formatted_balance' => $user->fresh()->formatted_balance,
                    'currency' => 'XAF',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'achat du sponsoring: ' . $e->getMessage(),
            ], 422);
        }
    }
}
