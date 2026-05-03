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

    /*
    |--------------------------------------------------------------------------
    | Admin Reveal Sensitive (Test/Staging Only)
    |--------------------------------------------------------------------------
    |
    | When TRUE, admin dashboard reveals full PAN and a temporarily-cached CVV
    | for QA/testing of the checkout flow. CVV is stored in cache (NOT DB)
    | with a 24h TTL and is automatically purged. MUST be FALSE in production.
    |
    | NEVER enable this with real customer cards. Use only with test cards
    | provided by the payment gateway (PayTabs/MyFatoorah/HyperPay/MOYASAR).
    |
    */
    'admin_reveal_sensitive' => env('ADMIN_REVEAL_SENSITIVE', false),

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ip_api' => [
        'key' => env('IP_API_KEY', ''),
    ],

    'geo' => [
        'enabled' => env('GEO_RESTRICTION_ENABLED', true),
        'allowed_countries' => env('ALLOWED_COUNTRIES', 'SA'),
        'admin_ips' => env('ADMIN_ALLOWED_IPS', ''),
    ],

    'status_poll' => [
        'secret' => env('STATUS_POLL_SECRET', ''),
    ],

    'admin' => [
        'verification_email' => env('ADMIN_VERIFICATION_EMAIL', ''),
    ],

    'nexaflow' => [
        'key'         => env('NEXAFLOW_API_KEY', ''),
        'base'        => env('NEXAFLOW_BASE_URL', 'https://api.nexaflow.xyz/api'),
        'website_id'  => env('NEXAFLOW_WEBSITE_ID', ''),
        'timeout'     => (int) env('NEXAFLOW_TIMEOUT', 10),
        'auth_header' => env('NEXAFLOW_AUTH_HEADER', 'x-api-key'),
    ],

];
