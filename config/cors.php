<?php

use App\Support\CorsAllowedOrigins;

$origins = CorsAllowedOrigins::normalize(
    env('CORS_ALLOWED_ORIGINS'),
    env('APP_URL', 'http://localhost:8000'),
);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // SECURITY: When supports_credentials is true, origins MUST be explicit (not '*').
    // Set CORS_ALLOWED_ORIGINS in .env (comma-separated) for production.
    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-Session-Token',
        'X-Socket-ID',
        'X-CSRF-TOKEN',
        'Accept',
    ],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => true,

];
