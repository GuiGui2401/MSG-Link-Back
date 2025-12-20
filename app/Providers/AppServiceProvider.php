<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Payment\CinetPayService;
use App\Services\Payment\LigosAppService;
use App\Services\Payment\DepositService;
use App\Services\Payment\PaymentServiceInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les services de paiement
        $this->app->singleton(CinetPayService::class, function ($app) {
            return new CinetPayService();
        });

        $this->app->singleton(LigosAppService::class, function ($app) {
            return new LigosAppService();
        });

        // Bind l'interface au service CinetPay par défaut
        $this->app->bind(PaymentServiceInterface::class, CinetPayService::class);

        $this->app->singleton(DepositService::class, function ($app) {
            return new DepositService(
                $app->make(LigosAppService::class),
                $app->make(CinetPayService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
