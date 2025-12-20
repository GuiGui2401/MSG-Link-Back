<?php

namespace App\Services;

use App\Models\User;
use App\Models\PremiumPass;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PremiumPassService
{
    /**
     * Obtenir le prix du passe premium depuis les settings ou utiliser le prix par défaut
     */
    public function getPrice(): int
    {
        // Essayer de récupérer le prix depuis les settings
        $setting = \App\Models\Setting::where('key', 'premium_pass_monthly_price')->first();

        if ($setting && is_numeric($setting->value)) {
            return (int) $setting->value;
        }

        return PremiumPass::MONTHLY_PRICE;
    }

    /**
     * Vérifier si un utilisateur a un passe premium actif
     */
    public function hasActivePremium(User $user): bool
    {
        return $user->is_premium
            && $user->premium_expires_at
            && $user->premium_expires_at->isFuture();
    }

    /**
     * Acheter ou renouveler le passe premium via le wallet
     */
    public function purchaseWithWallet(User $user, bool $autoRenew = false): array
    {
        try {
            DB::beginTransaction();

            $price = $this->getPrice();

            // Vérifier si l'utilisateur a assez de solde
            if (!$user->hasEnoughBalance($price)) {
                return [
                    'success' => false,
                    'message' => 'Solde insuffisant. Vous avez besoin de ' . number_format($price, 0, ',', ' ') . ' FCFA.',
                    'required_amount' => $price,
                    'current_balance' => $user->wallet_balance,
                ];
            }

            // Vérifier s'il a déjà un passe actif
            $existingPass = PremiumPass::getActive($user->id);

            if ($existingPass) {
                // Renouveler le passe existant
                $result = $this->renewPremium($user, $existingPass, $autoRenew);
            } else {
                // Créer un nouveau passe
                $result = $this->createNewPremium($user, $price, $autoRenew);
            }

            if ($result['success']) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'achat du passe premium', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'achat du passe premium.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Créer un nouveau passe premium
     */
    protected function createNewPremium(User $user, int $price, bool $autoRenew): array
    {
        // Débiter le wallet
        $user->debitWallet(
            $price,
            'Achat du passe premium - 1 mois',
            null
        );

        // Créer le passe premium
        $premiumPass = PremiumPass::create([
            'user_id' => $user->id,
            'amount' => $price,
            'payment_reference' => PremiumPass::generatePaymentReference(),
            'status' => PremiumPass::STATUS_PENDING,
            'auto_renew' => $autoRenew,
        ]);

        // Activer le passe
        $premiumPass->activate();

        return [
            'success' => true,
            'message' => 'Passe premium activé avec succès ! Vous êtes maintenant vérifié et pouvez voir l\'identité de tous les utilisateurs.',
            'premium_pass' => $premiumPass,
            'expires_at' => $premiumPass->expires_at,
            'days_remaining' => $premiumPass->days_remaining,
        ];
    }

    /**
     * Renouveler un passe premium existant
     */
    protected function renewPremium(User $user, PremiumPass $premiumPass, bool $autoRenew): array
    {
        $price = $this->getPrice();

        // Débiter le wallet
        $user->debitWallet(
            $price,
            'Renouvellement du passe premium - 1 mois',
            $premiumPass
        );

        // Mettre à jour l'auto-renouvellement si spécifié
        if ($autoRenew !== null) {
            $premiumPass->update(['auto_renew' => $autoRenew]);
        }

        // Renouveler le passe
        $premiumPass->renew();

        return [
            'success' => true,
            'message' => 'Passe premium renouvelé avec succès !',
            'premium_pass' => $premiumPass->fresh(),
            'expires_at' => $premiumPass->expires_at,
            'days_remaining' => $premiumPass->days_remaining,
        ];
    }

    /**
     * Annuler le renouvellement automatique
     */
    public function cancelAutoRenew(User $user): array
    {
        $premiumPass = PremiumPass::getActive($user->id);

        if (!$premiumPass) {
            return [
                'success' => false,
                'message' => 'Aucun passe premium actif trouvé.',
            ];
        }

        $premiumPass->update(['auto_renew' => false]);
        $user->update(['premium_auto_renew' => false]);

        return [
            'success' => true,
            'message' => 'Le renouvellement automatique a été désactivé. Votre passe expirera le ' . $premiumPass->expires_at->format('d/m/Y'),
        ];
    }

    /**
     * Activer le renouvellement automatique
     */
    public function enableAutoRenew(User $user): array
    {
        $premiumPass = PremiumPass::getActive($user->id);

        if (!$premiumPass) {
            return [
                'success' => false,
                'message' => 'Aucun passe premium actif trouvé.',
            ];
        }

        $premiumPass->update(['auto_renew' => true]);
        $user->update(['premium_auto_renew' => true]);

        return [
            'success' => true,
            'message' => 'Le renouvellement automatique a été activé.',
        ];
    }

    /**
     * Obtenir les statistiques du passe premium d'un utilisateur
     */
    public function getStats(User $user): array
    {
        $premiumPass = PremiumPass::getActive($user->id);
        $allPasses = PremiumPass::where('user_id', $user->id)->get();

        return [
            'is_premium' => $this->hasActivePremium($user),
            'current_pass' => $premiumPass,
            'total_spent' => $allPasses->sum('amount'),
            'total_passes' => $allPasses->count(),
            'active_passes' => $allPasses->where('status', PremiumPass::STATUS_ACTIVE)->count(),
            'expires_at' => $premiumPass ? $premiumPass->expires_at : null,
            'days_remaining' => $premiumPass ? $premiumPass->days_remaining : 0,
            'auto_renew' => $user->premium_auto_renew,
            'price' => $this->getPrice(),
        ];
    }

    /**
     * Traiter les renouvellements automatiques (à exécuter via un cron job)
     */
    public function processAutoRenewals(): array
    {
        $processed = 0;
        $failed = 0;

        // Récupérer les passes qui expirent dans les prochaines 24h avec auto-renew activé
        $expiring = PremiumPass::where('auto_renew', true)
            ->where('status', PremiumPass::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDay()])
            ->with('user')
            ->get();

        foreach ($expiring as $pass) {
            try {
                $result = $this->purchaseWithWallet($pass->user, true);

                if ($result['success']) {
                    $processed++;
                } else {
                    $failed++;
                    Log::warning('Échec du renouvellement automatique', [
                        'user_id' => $pass->user_id,
                        'reason' => $result['message'],
                    ]);
                }
            } catch (Exception $e) {
                $failed++;
                Log::error('Erreur lors du renouvellement automatique', [
                    'user_id' => $pass->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $expiring->count(),
        ];
    }

    /**
     * Marquer les passes expirés comme expirés (à exécuter via un cron job)
     */
    public function markExpiredPasses(): int
    {
        $expired = PremiumPass::expired()->get();

        foreach ($expired as $pass) {
            $pass->markAsExpired();
        }

        return $expired->count();
    }
}
