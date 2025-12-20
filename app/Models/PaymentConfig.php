<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    // Constantes pour les clés de configuration
    const KEY_DEPOSIT_PROVIDER = 'deposit_provider';
    const KEY_WITHDRAWAL_PROVIDER = 'withdrawal_provider';
    const KEY_GIFT_PROVIDER = 'gift_provider';
    const KEY_PREMIUM_PROVIDER = 'premium_provider';

    // Constantes pour les providers
    const PROVIDER_CINETPAY = 'cinetpay';
    const PROVIDER_LIGOSAPP = 'ligosapp';
    const PROVIDER_INTOUCH = 'intouch';

    /**
     * Obtenir une configuration par sa clé
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("payment_config_{$key}", 3600, function () use ($key, $default) {
            $config = self::where('key', $key)->first();
            return $config ? $config->value : $default;
        });
    }

    /**
     * Définir une configuration
     */
    public static function set(string $key, $value, ?string $description = null): self
    {
        $config = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        // Effacer le cache
        Cache::forget("payment_config_{$key}");

        return $config;
    }

    /**
     * Obtenir le provider pour les dépôts
     */
    public static function getDepositProvider(): string
    {
        return self::get(self::KEY_DEPOSIT_PROVIDER, self::PROVIDER_LIGOSAPP);
    }

    /**
     * Obtenir le provider pour les retraits
     */
    public static function getWithdrawalProvider(): string
    {
        return self::get(self::KEY_WITHDRAWAL_PROVIDER, self::PROVIDER_CINETPAY);
    }

    /**
     * Obtenir le provider pour les cadeaux
     */
    public static function getGiftProvider(): string
    {
        return self::get(self::KEY_GIFT_PROVIDER, self::PROVIDER_CINETPAY);
    }

    /**
     * Obtenir le provider pour les abonnements premium
     */
    public static function getPremiumProvider(): string
    {
        return self::get(self::KEY_PREMIUM_PROVIDER, self::PROVIDER_CINETPAY);
    }

    /**
     * Obtenir tous les providers disponibles
     */
    public static function getAvailableProviders(): array
    {
        return [
            self::PROVIDER_CINETPAY => [
                'name' => 'CinetPay',
                'icon' => 'credit-card',
                'color' => 'blue',
                'supports' => ['deposit', 'withdrawal', 'gift', 'premium'],
            ],
            self::PROVIDER_LIGOSAPP => [
                'name' => 'LygosApp',
                'icon' => 'mobile-alt',
                'color' => 'purple',
                'supports' => ['deposit', 'gift', 'premium'],
            ],
            self::PROVIDER_INTOUCH => [
                'name' => 'Intouch',
                'icon' => 'wallet',
                'color' => 'orange',
                'supports' => ['deposit', 'withdrawal', 'gift', 'premium'],
            ],
        ];
    }
}
