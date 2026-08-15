<?php

namespace App\Http\Controllers;

use App\Models\CustomerBlock;
use App\Services\GeoLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GeoCheckController
 *
 * يوفر endpoint خفيف لفحص الموقع الجغرافي من الـ frontend.
 * GET /api/geo/check → { is_saudi, access_scope, country, country_ar }
 */
class GeoCheckController extends Controller
{
    public function __construct(
        protected GeoLocationService $geoService,
    ) {}

    /**
     * فحص الموقع الجغرافي للزائر
     */
    public function check(Request $request): JsonResponse
    {
        // $request->ip() respects TrustProxies — only proxy CIDRs
        // declared in bootstrap/app.php are trusted. Safe from spoofing.
        $ip = $request->ip() ?? '127.0.0.1';
        $location = $this->geoService->getLocation($ip);

        $countryCode = $location['country_code'] ?? null;
        $isSaudi = $this->geoService->isSaudiArabia($ip);
        $isAdmin = $this->geoService->isAdminIp($ip);
        $sessionToken = (string) $request->header('X-Session-Token', $request->input('session_id', ''));
        $sessionToken = is_string($sessionToken) && strlen($sessionToken) <= 128 ? $sessionToken : '';

        // access_scope: تحديد نطاق الوصول بدون كشف حالة admin IP
        // full  = صلاحية كاملة (owner)
        // local = زائر محلي (سعودي) — الموقع بدون dashboard
        // blog  = زائر أجنبي — المدونة فقط
        $accessScope = $isAdmin ? 'full' : ($isSaudi ? 'local' : 'blog');

        return response()->json([
            'success' => true,
            'is_saudi' => $isSaudi,
            'access_scope' => $accessScope,
            'customer_blocked' => CustomerBlock::matches($sessionToken),
            'country' => $location['country'] ?? null,
            'country_code' => $countryCode,
            'country_ar' => $this->geoService->getArabicCountryName($countryCode),
        ]);
    }

}
