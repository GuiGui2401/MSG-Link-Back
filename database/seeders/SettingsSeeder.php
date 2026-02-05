<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ==================== CINETPAY PAYMENT ====================
            [
                'key' => 'cinetpay_api_key',
                'value' => env('CINETPAY_API_KEY', ''),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'CinetPay API Key',
            ],
            [
                'key' => 'cinetpay_site_id',
                'value' => env('CINETPAY_SITE_ID', ''),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'CinetPay Site ID',
            ],
            [
                'key' => 'cinetpay_secret_key',
                'value' => env('CINETPAY_SECRET_KEY', ''),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'CinetPay Secret Key',
            ],
            [
                'key' => 'cinetpay_notify_url',
                'value' => env('CINETPAY_NOTIFY_URL', ''),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'CinetPay Notification URL',
            ],
            [
                'key' => 'cinetpay_transfer_password',
                'value' => env('CINETPAY_TRANSFER_PASSWORD', ''),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'CinetPay Transfer Password',
            ],
            [
                'key' => 'default_payment_provider',
                'value' => env('DEFAULT_PAYMENT_PROVIDER', 'cinetpay'),
                'type' => 'string',
                'group' => 'payment',
                'description' => 'Provider de paiement par défaut',
            ],

            // ==================== PREMIUM ====================
            [
                'key' => 'premium_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'premium',
                'description' => 'Activer le mode premium',
            ],
            [
                'key' => 'premium_monthly_price',
                'value' => env('PREMIUM_PRICE', '450'),
                'type' => 'decimal',
                'group' => 'premium',
                'description' => 'Prix mensuel de l\'abonnement premium (en FCFA)',
            ],
            [
                'key' => 'premium_duration_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'premium',
                'description' => 'Durée de l\'abonnement premium (en jours)',
            ],
            [
                'key' => 'reveal_anonymous_price',
                'value' => '500',
                'type' => 'decimal',
                'group' => 'premium',
                'description' => 'Prix pour révéler l\'identité d\'un message anonyme (en FCFA)',
            ],

            // ==================== CADEAUX / GIFTS ====================
            [
                'key' => 'gifts_platform_fee_percent',
                'value' => env('PLATFORM_FEE_PERCENT', '5'),
                'type' => 'decimal',
                'group' => 'gifts',
                'description' => 'Commission plateforme sur les cadeaux (%)',
            ],
            [
                'key' => 'gift_price_bronze',
                'value' => '1000',
                'type' => 'decimal',
                'group' => 'gifts',
                'description' => 'Prix des cadeaux Bronze (en FCFA)',
            ],
            [
                'key' => 'gift_price_silver',
                'value' => '5000',
                'type' => 'decimal',
                'group' => 'gifts',
                'description' => 'Prix des cadeaux Silver (en FCFA)',
            ],
            [
                'key' => 'gift_price_gold',
                'value' => '25000',
                'type' => 'decimal',
                'group' => 'gifts',
                'description' => 'Prix des cadeaux Gold (en FCFA)',
            ],
            [
                'key' => 'gift_price_diamond',
                'value' => '50000',
                'type' => 'decimal',
                'group' => 'gifts',
                'description' => 'Prix des cadeaux Diamond (en FCFA)',
            ],

            // ==================== MONETISATION ====================
            [
                'key' => 'monetization_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'monetization',
                'description' => 'Activer la monétisation',
            ],
            [
                'key' => 'creator_fund_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'monetization',
                'description' => 'Activer le Creator Fund',
            ],
            [
                'key' => 'creator_fund_pool_amount',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'monetization',
                'description' => 'Montant du Creator Fund par période (en FCFA)',
            ],
            [
                'key' => 'creator_fund_period_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'monetization',
                'description' => 'Durée de la période Creator Fund (en jours)',
            ],
            [
                'key' => 'ads_revenue_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'monetization',
                'description' => 'Activer le partage des revenus publicitaires',
            ],
            [
                'key' => 'ads_revenue_pool_amount',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'monetization',
                'description' => 'Montant brut des revenus publicitaires par période (en FCFA)',
            ],
            [
                'key' => 'ads_revenue_share_percent',
                'value' => '50',
                'type' => 'decimal',
                'group' => 'monetization',
                'description' => 'Part créateurs des revenus publicitaires (%)',
            ],
            [
                'key' => 'engagement_view_weight',
                'value' => '1',
                'type' => 'decimal',
                'group' => 'monetization',
                'description' => 'Poids des vues dans le score d\'engagement',
            ],
            [
                'key' => 'engagement_like_weight',
                'value' => '3',
                'type' => 'decimal',
                'group' => 'monetization',
                'description' => 'Poids des likes dans le score d\'engagement',
            ],
            [
                'key' => 'monetization_min_payout',
                'value' => '100',
                'type' => 'decimal',
                'group' => 'monetization',
                'description' => 'Seuil minimum de paiement (en FCFA)',
            ],
            [
                'key' => 'monetization_auto_payout',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'monetization',
                'description' => 'Payer automatiquement les gains',
            ],

            // ==================== WALLET ====================
            [
                'key' => 'wallet_min_withdrawal',
                'value' => env('MIN_WITHDRAWAL_AMOUNT', '1000'),
                'type' => 'decimal',
                'group' => 'wallet',
                'description' => 'Montant minimum de retrait (en FCFA)',
            ],
            [
                'key' => 'wallet_withdrawal_fee',
                'value' => env('WITHDRAWAL_FEE', '0'),
                'type' => 'decimal',
                'group' => 'wallet',
                'description' => 'Frais de retrait (en FCFA)',
            ],
            [
                'key' => 'wallet_currency',
                'value' => 'XAF',
                'type' => 'string',
                'group' => 'wallet',
                'description' => 'Devise par défaut',
            ],

            // ==================== CHAT / FLAME ====================
            [
                'key' => 'chat_flame_yellow_days',
                'value' => '2',
                'type' => 'integer',
                'group' => 'chat',
                'description' => 'Jours pour flame jaune',
            ],
            [
                'key' => 'chat_flame_orange_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'chat',
                'description' => 'Jours pour flame orange',
            ],
            [
                'key' => 'chat_flame_purple_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'chat',
                'description' => 'Jours pour flame violette',
            ],
            [
                'key' => 'chat_streak_expiration_hours',
                'value' => '48',
                'type' => 'integer',
                'group' => 'chat',
                'description' => 'Durée d\'expiration du streak (en heures)',
            ],

            // ==================== RATE LIMITING ====================
            [
                'key' => 'rate_messages_per_minute',
                'value' => env('MESSAGES_RATE_LIMIT', '10'),
                'type' => 'integer',
                'group' => 'rate_limits',
                'description' => 'Messages anonymes par minute',
            ],
            [
                'key' => 'rate_confessions_per_hour',
                'value' => env('CONFESSIONS_RATE_LIMIT', '5'),
                'type' => 'integer',
                'group' => 'rate_limits',
                'description' => 'Confessions par heure',
            ],
            [
                'key' => 'rate_login_attempts_per_minute',
                'value' => '5',
                'type' => 'integer',
                'group' => 'rate_limits',
                'description' => 'Tentatives de connexion par minute',
            ],

            // ==================== MODERATION ====================
            [
                'key' => 'moderation_ai_enabled',
                'value' => env('MODERATION_AI_ENABLED', '0'),
                'type' => 'boolean',
                'group' => 'moderation',
                'description' => 'Activer la modération IA',
            ],
            [
                'key' => 'moderation_max_message_length',
                'value' => '1000',
                'type' => 'integer',
                'group' => 'moderation',
                'description' => 'Longueur maximum des messages',
            ],
            [
                'key' => 'moderation_max_confession_length',
                'value' => '2000',
                'type' => 'integer',
                'group' => 'moderation',
                'description' => 'Longueur maximum des confessions',
            ],

            // ==================== SECURITY ====================
            [
                'key' => 'security_verification_code_expiry',
                'value' => '15',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Durée de validité des codes de vérification (minutes)',
            ],
            [
                'key' => 'security_verification_code_length',
                'value' => '6',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Longueur des codes de vérification',
            ],
            [
                'key' => 'security_session_lifetime_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Durée de session (jours)',
            ],

            // ==================== GROUPS ====================
            [
                'key' => 'group_max_members_default',
                'value' => '50',
                'type' => 'integer',
                'group' => 'groups',
                'description' => 'Nombre maximum de membres par groupe (par défaut)',
            ],
            [
                'key' => 'group_max_members_premium',
                'value' => '200',
                'type' => 'integer',
                'group' => 'groups',
                'description' => 'Nombre maximum de membres par groupe (premium)',
            ],
            [
                'key' => 'group_max_per_user',
                'value' => '10',
                'type' => 'integer',
                'group' => 'groups',
                'description' => 'Nombre maximum de groupes par utilisateur',
            ],

            // ==================== PROMOTIONS / BOOST ====================
            [
                'key' => 'promotion_min_budget',
                'value' => '1000',
                'type' => 'decimal',
                'group' => 'promotions',
                'description' => 'Budget minimum pour une promotion (en FCFA)',
            ],
            [
                'key' => 'promotion_min_duration_days',
                'value' => '1',
                'type' => 'integer',
                'group' => 'promotions',
                'description' => 'Durée minimum d\'une promotion (en jours)',
            ],
            [
                'key' => 'promotion_max_duration_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'promotions',
                'description' => 'Durée maximum d\'une promotion (en jours)',
            ],
            [
                'key' => 'promotion_platform_fee_percent',
                'value' => '10',
                'type' => 'decimal',
                'group' => 'promotions',
                'description' => 'Commission plateforme sur les promotions (%)',
            ],
            [
                'key' => 'promotion_allow_images',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'promotions',
                'description' => 'Autoriser la promotion d\'images (pas seulement vidéos)',
            ],

            // ==================== DEPOSIT / WITHDRAWAL ====================
            [
                'key' => 'deposit_min_amount',
                'value' => '500',
                'type' => 'decimal',
                'group' => 'wallet',
                'description' => 'Montant minimum de dépôt (en FCFA)',
            ],

            // ==================== GENERAL ====================
            [
                'key' => 'app_name',
                'value' => env('APP_NAME', 'Weylo'),
                'type' => 'string',
                'group' => 'general',
                'description' => 'Nom de l\'application',
            ],
            [
                'key' => 'app_frontend_url',
                'value' => env('APP_FRONTEND_URL', 'http://localhost:3000'),
                'type' => 'string',
                'group' => 'general',
                'description' => 'URL du frontend',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
