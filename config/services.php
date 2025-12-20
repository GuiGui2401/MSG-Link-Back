<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Services
    |--------------------------------------------------------------------------
    */

    'ligosapp' => [
        'api_key' => env('LIGOSAPP_API_KEY'),
        'api_secret' => env('LIGOSAPP_API_SECRET'),
        'webhook_secret' => env('LIGOSAPP_WEBHOOK_SECRET'),
        'base_url' => env('LIGOSAPP_BASE_URL', 'https://api.lygosapp.com/v1'),
    ],

    'intouch' => [
        'api_key' => env('INTOUCH_API_KEY'),
        'secret' => env('INTOUCH_SECRET'),
        'base_url' => env('INTOUCH_BASE_URL', 'https://api.intouch.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase (Push Notifications)
    |--------------------------------------------------------------------------
    */

    'firebase' => [
        'credentials' => base_path(env('FIREBASE_CREDENTIALS', 'storage/app/firebase/firebase-credentials.json')),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Weylo App Settings
    |--------------------------------------------------------------------------
    */

    'msglink' => [
        'premium_price' => env('PREMIUM_PRICE', 450),
        'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 5),
        'min_withdrawal_amount' => env('MIN_WITHDRAWAL_AMOUNT', 1000),
        'withdrawal_fee' => env('WITHDRAWAL_FEE', 0),
    ],

];
