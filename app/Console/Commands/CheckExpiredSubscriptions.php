<?php

namespace App\Console\Commands;

use App\Models\PremiumSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';

    protected $description = 'Check and mark expired subscriptions, send renewal reminders';

    public function __construct(private NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking expired subscriptions...');

        // Marquer les abonnements expirés
        $expiredCount = PremiumSubscription::active()
            ->where('expires_at', '<', now())
            ->update(['status' => PremiumSubscription::STATUS_EXPIRED]);

        $this->info("Marked {$expiredCount} subscriptions as expired.");

        // Envoyer des rappels pour les abonnements qui expirent bientôt
        $this->sendExpirationReminders();

        // Auto-renouveler les abonnements éligibles
        $this->processAutoRenewals();

        $this->info('Done!');

        return Command::SUCCESS;
    }

    private function sendExpirationReminders(): void
    {
        // Rappel 3 jours avant expiration
        $expiringIn3Days = PremiumSubscription::active()
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(3))
            ->whereNull('last_reminder_sent_at')
            ->with('subscriber')
            ->get();

        foreach ($expiringIn3Days as $subscription) {
            $daysRemaining = now()->diffInDays($subscription->expires_at);
            
            $this->notificationService->sendSubscriptionExpiringNotification(
                $subscription->subscriber,
                $daysRemaining
            );

            $subscription->update(['last_reminder_sent_at' => now()]);
        }

        $this->info("Sent {$expiringIn3Days->count()} expiration reminders.");
    }

    private function processAutoRenewals(): void
    {
        $toRenew = PremiumSubscription::active()
            ->where('auto_renew', true)
            ->where('expires_at', '<', now()->addDay())
            ->with('subscriber')
            ->get();

        $renewedCount = 0;

        foreach ($toRenew as $subscription) {
            try {
                // Vérifier si l'utilisateur a le solde suffisant
                if ($subscription->subscriber->hasEnoughBalance(PremiumSubscription::MONTHLY_PRICE)) {
                    // Débiter le wallet et renouveler
                    $subscription->subscriber->debitWallet(
                        PremiumSubscription::MONTHLY_PRICE,
                        'Renouvellement automatique abonnement premium',
                        $subscription
                    );

                    $subscription->renew();
                    $renewedCount++;

                    Log::info('Subscription auto-renewed', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->subscriber_id,
                    ]);
                } else {
                    // Désactiver le renouvellement automatique
                    $subscription->update(['auto_renew' => false]);

                    Log::info('Auto-renewal failed - insufficient balance', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->subscriber_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Auto-renewal error', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Auto-renewed {$renewedCount} subscriptions.");
    }
}
