<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentConfigController extends Controller
{
    /**
     * Afficher la page de configuration des paiements
     */
    public function index()
    {
        $configs = PaymentConfig::all()->keyBy('key');
        $providers = PaymentConfig::getAvailableProviders();

        return view('admin.payment-config.index', compact('configs', 'providers'));
    }

    /**
     * Mettre à jour la configuration des paiements
     */
    public function update(Request $request)
    {
        $request->validate([
            'deposit_provider' => 'required|in:cinetpay,ligosapp,intouch',
            'withdrawal_provider' => 'required|in:cinetpay,ligosapp,intouch',
            'gift_provider' => 'required|in:cinetpay,ligosapp,intouch',
            'premium_provider' => 'required|in:cinetpay,ligosapp,intouch',
        ]);

        // Mettre à jour chaque configuration
        PaymentConfig::set(
            PaymentConfig::KEY_DEPOSIT_PROVIDER,
            $request->deposit_provider,
            'Provider pour les dépôts wallet'
        );

        PaymentConfig::set(
            PaymentConfig::KEY_WITHDRAWAL_PROVIDER,
            $request->withdrawal_provider,
            'Provider pour les retraits wallet'
        );

        PaymentConfig::set(
            PaymentConfig::KEY_GIFT_PROVIDER,
            $request->gift_provider,
            'Provider pour les paiements de cadeaux'
        );

        PaymentConfig::set(
            PaymentConfig::KEY_PREMIUM_PROVIDER,
            $request->premium_provider,
            'Provider pour les abonnements premium'
        );

        // Effacer tout le cache de configuration
        Cache::flush();

        return redirect()
            ->route('admin.payment-config.index')
            ->with('success', 'Configuration des paiements mise à jour avec succès.');
    }
}
