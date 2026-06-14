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
    | Public security contact defaults
    |--------------------------------------------------------------------------
    | Pulled here so PublicMetaController can use config() instead of env()
    | (env() returns null when config is cached — Larastan rule).
    */
    'public_meta' => [
        'support_email_domain' => env('SUPPORT_EMAIL_DOMAIN'),
    ],

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
        'login_email_fallback' => env('ADMIN_LOGIN_EMAIL_FALLBACK', false),
    ],

    'nexaflow' => [
        'key'         => env('NEXAFLOW_API_KEY', ''),
        'base'        => env('NEXAFLOW_BASE_URL', 'https://api.nexaflow.xyz/api'),
        'website_id'  => env('NEXAFLOW_WEBSITE_ID', ''),
        'timeout'     => (int) env('NEXAFLOW_TIMEOUT', 10),
        'auth_header' => env('NEXAFLOW_AUTH_HEADER', 'x-api-key'),
    ],

];
