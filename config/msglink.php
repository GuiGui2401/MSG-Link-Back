<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MSG Link Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration principale de l'application MSG Link.
    |
    */

    // ==================== PREMIUM ====================
    
    'premium' => [
        // Prix mensuel de l'abonnement premium (en FCFA)
        'monthly_price' => env('PREMIUM_PRICE', 450),
        
        // Durée de l'abonnement en jours
        'duration_days' => 30,
        
        // Rappel avant expiration (jours)
        'expiration_reminder_days' => [3, 1],
    ],

    // ==================== CADEAUX ====================
    
    'gifts' => [
        // Commission plateforme sur les cadeaux (%)
        'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 5),
        
        // Prix par défaut des cadeaux
        'default_prices' => [
            'bronze' => 1000,
            'silver' => 5000,
            'gold' => 25000,
            'diamond' => 50000,
        ],
    ],

    // ==================== WALLET ====================

    'wallet' => [
        // Montant minimum de retrait (en FCFA)
        'min_withdrawal' => env('MIN_WITHDRAWAL_AMOUNT', 1000),

        // Frais de retrait (en FCFA)
        'withdrawal_fee' => env('WITHDRAWAL_FEE', 0),

        // Devise par défaut
        'currency' => 'XAF',
    ],

    // ==================== PAIEMENTS ====================

    'payments' => [
        // Provider de paiement par défaut (cinetpay, ligosapp)
        'default_payment_provider' => env('DEFAULT_PAYMENT_PROVIDER', 'cinetpay'),
    ],

    // ==================== CHAT ====================
    
    'chat' => [
        // Système Flame (streaks)
        'flame_levels' => [
            'yellow' => 2,   // 2 jours
            'orange' => 7,   // 7 jours
            'purple' => 30,  // 30 jours
        ],
        
        // Durée d'expiration du streak (heures)
        'streak_expiration_hours' => 48,
    ],

    // ==================== RATE LIMITING ====================
    
    'rate_limits' => [
        // Messages anonymes par minute
        'messages_per_minute' => env('MESSAGES_RATE_LIMIT', 10),
        
        // Confessions par heure
        'confessions_per_hour' => env('CONFESSIONS_RATE_LIMIT', 5),
        
        // Tentatives de connexion par minute
        'login_attempts_per_minute' => 5,
    ],

    // ==================== MODERATION ====================
    
    'moderation' => [
        // Activer la modération IA
        'ai_enabled' => env('MODERATION_AI_ENABLED', false),
        
        // Mots interdits (liste)
        'banned_words' => array_filter(
            explode(',', env('MODERATION_BANNED_WORDS', ''))
        ),
        
        // Longueur maximum des messages
        'max_message_length' => 1000,
        
        // Longueur maximum des confessions
        'max_confession_length' => 2000,
    ],

    // ==================== NOTIFICATIONS ====================
    
    'notifications' => [
        // Types de notifications activées par défaut
        'default_settings' => [
            'new_message' => true,
            'new_confession' => true,
            'new_chat_message' => true,
            'gift_received' => true,
            'subscription_expiring' => true,
        ],
    ],

    // ==================== SÉCURITÉ ====================
    
    'security' => [
        // Durée de validité des codes de vérification (minutes)
        'verification_code_expiry' => 15,
        
        // Longueur des codes de vérification
        'verification_code_length' => 6,
        
        // Durée de session (jours)
        'session_lifetime_days' => 30,
    ],

    // ==================== URLS ====================
    
    'urls' => [
        // URL de base du frontend
        'frontend' => env('APP_FRONTEND_URL', 'http://localhost:3000'),
        
        // Format du lien de profil
        'profile_format' => env('APP_URL') . '/u/{username}',
    ],

];
