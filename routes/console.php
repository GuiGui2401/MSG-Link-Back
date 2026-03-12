<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Ce fichier définit les commandes Artisan et les tâches planifiées
| pour Weylo.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Vérifier les abonnements expirés toutes les heures
Schedule::command('subscriptions:check-expired')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Calculer les streaks de conversation toutes les heures pour décrémentation progressive
Schedule::command('chat:calculate-streaks')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Envoyer les rappels d'expiration d'abonnement tous les jours à 9h
Schedule::command('subscriptions:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Nettoyer les codes de vérification expirés toutes les 6 heures
Schedule::command('verification:cleanup')
    ->everySixHours()
    ->withoutOverlapping();

// Nettoyer les tokens de session expirés tous les jours
Schedule::command('sanctum:prune-expired --hours=720') // 30 jours
    ->daily()
    ->withoutOverlapping();

// Supprimer les notifications lues de plus de 30 jours
Schedule::command('notifications:cleanup')
    ->weekly()
    ->withoutOverlapping();

// Nettoyer les stories expirées toutes les heures
Schedule::command('stories:cleanup')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

/*
|--------------------------------------------------------------------------
| Wallet & Payment Tasks
|--------------------------------------------------------------------------
*/

use App\Jobs\Wallet\CheckPendingDepositsJob;
use App\Jobs\Wallet\CheckPendingWithdrawalsJob;
use App\Jobs\Wallet\CleanupStaleTransactionsJob;

// Vérifier les dépôts pending toutes les 30 secondes
Schedule::job(new CheckPendingDepositsJob)
    ->everyThirtySeconds()
    ->withoutOverlapping();

// Vérifier les retraits pending toutes les minutes
Schedule::job(new CheckPendingWithdrawalsJob)
    ->everyMinute()
    ->withoutOverlapping();

// Nettoyer les transactions obsolètes (> 24h pending) tous les jours à 3h du matin
Schedule::job(new CleanupStaleTransactionsJob)
    ->dailyAt('03:00')
    ->withoutOverlapping();
