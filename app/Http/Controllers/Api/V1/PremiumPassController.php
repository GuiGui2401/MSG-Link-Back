<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PremiumPassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PremiumPassController extends Controller
{
    public function __construct(
        private PremiumPassService $premiumPassService
    ) {}

    /**
     * Obtenir les informations sur le passe premium
     * GET /api/v1/premium-pass/info
     */
    public function info(): JsonResponse
    {
        $price = $this->premiumPassService->getPrice();

        return response()->json([
            'price' => $price,
            'formatted_price' => number_format($price, 0, ',', ' ') . ' FCFA',
            'duration' => '1 mois',
            'features' => [
                'Compte vérifié avec badge bleu',
                'Voir l\'identité de tous les utilisateurs',
                'Plus d\'anonymat dans les confessions',
                'Voir qui vous envoie des messages anonymes',
                'Identité révélée dans les stories',
                'Identité visible dans les groupes',
                'Accès prioritaire aux nouvelles fonctionnalités',
            ],
        ]);
    }

    /**
     * Obtenir le statut premium de l'utilisateur
     * GET /api/v1/premium-pass/status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->premiumPassService->getStats($user);

        return response()->json([
            'is_premium' => $stats['is_premium'],
            'is_verified' => $user->is_verified,
            'current_pass' => $stats['current_pass'],
            'expires_at' => $stats['expires_at'],
            'days_remaining' => $stats['days_remaining'],
            'auto_renew' => $stats['auto_renew'],
            'total_spent' => $stats['total_spent'],
            'total_passes' => $stats['total_passes'],
            'price' => $stats['price'],
        ]);
    }

    /**
     * Acheter le passe premium via le wallet
     * POST /api/v1/premium-pass/purchase
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'auto_renew' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $autoRenew = $request->boolean('auto_renew', false);

        $result = $this->premiumPassService->purchaseWithWallet($user, $autoRenew);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'premium_pass' => $result['premium_pass'],
                    'expires_at' => $result['expires_at'],
                    'days_remaining' => $result['days_remaining'],
                    'user' => [
                        'is_premium' => true,
                        'is_verified' => true,
                        'wallet_balance' => $user->fresh()->wallet_balance,
                    ],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'data' => $result,
        ], 400);
    }

    /**
     * Renouveler le passe premium
     * POST /api/v1/premium-pass/renew
     */
    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'auto_renew' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $autoRenew = $request->boolean('auto_renew', false);

        // Le renouvellement utilise la même logique que l'achat
        $result = $this->premiumPassService->purchaseWithWallet($user, $autoRenew);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'premium_pass' => $result['premium_pass'],
                    'expires_at' => $result['expires_at'],
                    'days_remaining' => $result['days_remaining'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'data' => $result,
        ], 400);
    }

    /**
     * Activer le renouvellement automatique
     * POST /api/v1/premium-pass/auto-renew/enable
     */
    public function enableAutoRenew(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->premiumPassService->enableAutoRenew($user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Désactiver le renouvellement automatique
     * POST /api/v1/premium-pass/auto-renew/disable
     */
    public function disableAutoRenew(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->premiumPassService->cancelAutoRenew($user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Historique des passes premium
     * GET /api/v1/premium-pass/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $passes = \App\Models\PremiumPass::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($passes);
    }

    /**
     * Vérifier si l'utilisateur peut voir l'identité d'un autre utilisateur
     * GET /api/v1/premium-pass/can-view-identity/{userId}
     */
    public function canViewIdentity(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();

        // Si l'utilisateur a le passe premium, il peut voir toutes les identités
        $canView = $this->premiumPassService->hasActivePremium($user);

        return response()->json([
            'can_view' => $canView,
            'is_premium' => $user->is_premium,
            'reason' => $canView
                ? 'Vous avez un passe premium actif'
                : 'Vous devez avoir un passe premium pour voir les identités',
        ]);
    }
}
