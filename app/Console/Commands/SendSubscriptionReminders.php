<?php

namespace App\Console\Commands;

use App\Models\PremiumSubscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:send-reminders';
    
    protected $description = 'Envoyer des rappels pour les abonnements qui expirent bientôt';

    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $reminderDays = config('msglink.premium.expiration_reminder_days', [3, 1]);
        $totalSent = 0;

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $subscriptions = PremiumSubscription::active()
                ->whereDate('expires_at', $targetDate)
                ->where('auto_renew', false)
                ->with('subscriber')
                ->get();

            foreach ($subscriptions as $subscription) {
                $this->notificationService->sendSubscriptionExpiringNotification(
                    $subscription->subscriber,
                    $days
                );
                $totalSent++;
            }

            $this->info("Envoyé {$subscriptions->count()} rappels pour expiration dans {$days} jour(s).");
        }

        $this->info("Total: {$totalSent} rappels envoyés.");

        return self::SUCCESS;
    }
}
