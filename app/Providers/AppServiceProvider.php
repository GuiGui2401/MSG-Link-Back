<?php

namespace App\Providers;

use App\Services\Payment\CinetPayService;
use App\Services\Payment\LigosAppService;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Support\ServiceProvider;

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

        // Bind par défaut vers CinetPay (peut être changé selon la configuration)
        $this->app->bind(PaymentServiceInterface::class, function ($app) {
            $defaultProvider = config('msglink.payments.default_payment_provider', 'cinetpay');

            return match ($defaultProvider) {
                'ligosapp' => $app->make(LigosAppService::class),
                default => $app->make(CinetPayService::class),
            };
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
