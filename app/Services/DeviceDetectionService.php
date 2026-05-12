<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Detect device type and browser from User-Agent.
 *
 * Intended for analytics/admin display only.
 * User-Agent parsing is heuristic and not security-sensitive
 * (User-Agent can be spoofed trivially).
 *
 * Consolidates the duplicate implementations that existed in
 * CustomerTrackingController and QuoteTrackingController.
 */
class DeviceDetectionService
{
    /**
     * Detect device type from User-Agent header.
     *
     * @return string mobile|tablet|desktop
     */
    public static function detectType(Request $request): string
    {
        return self::detectTypeFromUserAgent($request->userAgent());
    }

    /**
     * Detect browser name from User-Agent header.
     *
     * @return string Chrome|Safari|Firefox|Edge|Opera|Samsung Internet|Other
     */
    public static function detectBrowser(Request $request): string
    {
        return self::detectBrowserFromUserAgent($request->userAgent());
    }

    /**
     * Convenience method returning both device_type and browser.
     *
     * @return array{device_type: string, browser: string}
     */
    public static function detect(Request $request): array
    {
        $ua = $request->userAgent();

        return [
            'device_type' => self::detectTypeFromUserAgent($ua),
            'browser' => self::detectBrowserFromUserAgent($ua),
        ];
    }

    /**
     * Pure helper for unit testing.
     *
     * @return string mobile|tablet|desktop
     */
    public static function detectTypeFromUserAgent(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        if ($ua === '') {
            return 'desktop';
        }

        // Tablet first — some tablets also contain "mobile".
        // Android tablets typically omit "Mobile" from the UA.
        if (
            str_contains($ua, 'ipad')
            || str_contains($ua, 'tablet')
            || str_contains($ua, 'kindle')
            || str_contains($ua, 'silk')
            || str_contains($ua, 'playbook')
            || preg_match('/android(?!.*mobile)/i', $ua) === 1
        ) {
            return 'tablet';
        }

        if (
            str_contains($ua, 'mobile')
            || str_contains($ua, 'iphone')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'android')
        ) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Pure helper for unit testing.
     *
     * Order matters: Chromium-based browsers (Edge/Opera/Samsung) embed
     * "Chrome" in their UA and must be checked before Chrome.
     *
     * @return string Chrome|Safari|Firefox|Edge|Opera|Samsung Internet|Other
     */
    public static function detectBrowserFromUserAgent(?string $userAgent): string
    {
        $ua = $userAgent ?? '';

        if ($ua === '') {
            return 'Other';
        }

        if (
            str_contains($ua, 'Edg/')
            || str_contains($ua, 'EdgA/')
            || str_contains($ua, 'EdgiOS/')
        ) {
            return 'Edge';
        }

        if (
            str_contains($ua, 'OPR/')
            || str_contains($ua, 'Opera/')
        ) {
            return 'Opera';
        }

        if (str_contains($ua, 'SamsungBrowser/')) {
            return 'Samsung Internet';
        }

        if (
            str_contains($ua, 'Firefox/')
            || str_contains($ua, 'FxiOS/')
        ) {
            return 'Firefox';
        }

        if (
            str_contains($ua, 'Chrome/')
            || str_contains($ua, 'CriOS/')
            || str_contains($ua, 'Chromium/')
        ) {
            return 'Chrome';
        }

        if (
            str_contains($ua, 'Safari/')
            && ! str_contains($ua, 'Chrome/')
            && ! str_contains($ua, 'CriOS/')
            && ! str_contains($ua, 'Chromium/')
            && ! str_contains($ua, 'Android')
        ) {
            return 'Safari';
        }

        return 'Other';
    }
}
