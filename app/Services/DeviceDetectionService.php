<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Detect device type and browser from User-Agent.
 *
 * Consolidates the duplicate implementations that existed in
 * CustomerTrackingController and QuoteTrackingController.
 */
class DeviceDetectionService
{
    /**
     * Detect device type from User-Agent header.
     *
     * @return string  mobile|tablet|desktop
     */
    public static function detectType(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) return 'mobile';
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) return 'tablet';
        return 'desktop';
    }

    /**
     * Detect browser name from User-Agent header.
     *
     * @return string  Chrome|Safari|Firefox|Edge|Other
     */
    public static function detectBrowser(Request $request): string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) return 'Chrome';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Edg')) return 'Edge';
        return 'Other';
    }
}
