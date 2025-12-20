<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentConfig;
use Illuminate\Http\JsonResponse;

class PaymentProviderController extends Controller
{
    /**
     * Obtenir la configuration des providers de paiement
     */
    public function getConfig(): JsonResponse
    {
        $providers = PaymentConfig::getAvailableProviders();

        return response()->json([
            'providers' => [
                'deposit' => [
                    'active' => PaymentConfig::getDepositProvider(),
                    'available' => array_keys(array_filter($providers, function($provider) {
                        return in_array('deposit', $provider['supports']);
                    })),
                ],
                'withdrawal' => [
                    'active' => PaymentConfig::getWithdrawalProvider(),
                    'available' => array_keys(array_filter($providers, function($provider) {
                        return in_array('withdrawal', $provider['supports']);
                    })),
                ],
                'gift' => [
                    'active' => PaymentConfig::getGiftProvider(),
                    'available' => array_keys(array_filter($providers, function($provider) {
                        return in_array('gift', $provider['supports']);
                    })),
                ],
                'premium' => [
                    'active' => PaymentConfig::getPremiumProvider(),
                    'available' => array_keys(array_filter($providers, function($provider) {
                        return in_array('premium', $provider['supports']);
                    })),
                ],
            ],
            'providers_info' => $providers,
        ]);
    }
}
