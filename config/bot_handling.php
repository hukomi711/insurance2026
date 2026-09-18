<?php

/**
 * Configuration for bot and crawler handling.
 *
 * Controls which bots are blocked, which are allowed for monitoring,
 * and which endpoints are exempt from bot filtering.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Bot Blocking Enabled
    |--------------------------------------------------------------------------
    |
    | Global switch to enable/disable bot and crawler blocking.
    | When false, all bots are allowed regardless of other settings.
    |
    */
    'blocking_enabled' => (bool) env('BOT_BLOCKING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed/Whitelisted Bot User-Agent Fragments
    |--------------------------------------------------------------------------
    |
    | List of bot user-agent patterns that are ALLOWED even if they match
    | blocking patterns. These are trusted uptime monitoring and health check
    | services that should always be permitted.
    |
    | Format: lowercase strings to match against user-agent (case-insensitive)
    |
    */
    'allowed_bot_patterns' => [
        // Uptime monitoring services
        'uptimerobot',           // UptimeRobot - popular monitoring service
        'uptime.com',            // Uptime.com monitoring
        'statuspage',            // Statuspage.io health checks
        'pingdom',               // Pingdom monitoring
        'monitis',               // Monitis monitoring
        'monitoringservices',    // Generic monitoring
        'site24x7',              // Site24x7 monitoring
        'freshping',             // FreshPing monitoring
        'checkmy.site',          // CheckMySite monitoring
        'kevinahang',            // Kevinahang uptime bot

        // Synthetic monitoring
        'synthetic-monitoring',  // Elastic synthetics
        'elastic-synthetics',    // Elastic monitoring bot

        // Cloud platform monitoring
        'cloudflare-healthchecks',  // Cloudflare health check bot
        'aws-health-check',         // AWS health checks
        'gstatic.com',              // Google's internal health checks

        // Other critical monitoring
        'gtmetrix',              // GTmetrix performance monitoring
        'pagespeed',             // Google PageSpeed Insights
        'lighthouse',            // Google Lighthouse CI

        // Internal/custom
        'insurance2026-monitor', // Internal monitoring bot
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Path Bypass
    |--------------------------------------------------------------------------
    |
    | Paths that are completely exempt from bot blocking, even if the
    | user-agent matches a blocked pattern. These are critical for
    | uptime monitoring and internal health checks.
    |
    | Note: Paths are specified WITHOUT leading slash (as returned by
    | $request->path() in Laravel).
    |
    */
    'health_check_paths' => [
        'api/health',
        'api/health/queues',
        'api/health/realtime',
        'api/health/database',
        'api/health/cache',
        '.well-known/health',
        'ping',
        'health',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocking Patterns (Blocked by Default)
    |--------------------------------------------------------------------------
    |
    | User-agent fragments that trigger bot blocking.
    | These are checked AFTER the allowlist, so allowed bots won't be blocked.
    |
    */
    'blocked_patterns' => [
        'bot',
        'crawl',
        'crawler',
        'spider',
        'slurp',
        'mediapartners-google',
        'google-structured-data-testing-tool',
        'facebookexternalhit',
        'facebot',
        'linkedinbot',
        'embedly',
        'pinterest',
        'whatsapp',
        'telegrambot',
        'discordbot',
        'applebot',
        'bingbot',
        'yandex',
        'duckduckbot',
        'semrush',
        'ahrefsbot',
        'mj12bot',
        'dotbot',
        'python-requests',
        'curl/',
        'wget',
        'headless',
        'selenium',
        'playwright',
        'puppeteer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Whitelist IP Addresses (Optional)
    |--------------------------------------------------------------------------
    |
    | IP addresses that are allowed regardless of user-agent.
    | Useful for whitelisting known monitoring service IPs.
    | Format: CIDR notation or exact IP addresses.
    |
    | Note: IPs are resolved respecting X-Forwarded-For in trusted proxy mode.
    |
    */
    'allowed_ips' => [
        // Examples (uncomment and add actual IPs as needed):
        // '192.0.2.0/24',           // CIDR block
        // '198.51.100.42',          // Exact IP
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring
    |--------------------------------------------------------------------------
    |
    | Whether to log bot blocking events to application logs.
    | Useful for debugging and monitoring blocking behavior.
    |
    */
    'log_blocked_bots' => (bool) env('BOT_BLOCKING_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP response details for blocked bots.
    |
    */
    'response' => [
        'status_code' => 403,
        'body' => 'Forbidden',
        'content_type' => 'text/plain; charset=UTF-8',
        'cache_control' => 'no-cache, no-store, must-revalidate',
    ],
];
