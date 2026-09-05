<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\CustomerBlock;
use App\Services\GeoLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provides the frontend with the visitor's geographic access policy.
 *
 * GET /api/geo/check
 */
class GeoCheckController extends Controller
{
    public function __construct(
        protected GeoLocationService $geoService,
    ) {}

    public function check(Request $request): JsonResponse
    {
        // $request->ip() respects only the proxies trusted in bootstrap/app.php.
        $ip = $request->ip() ?? '127.0.0.1';
        $location = $this->geoService->getLocation($ip);

        $countryCode = $location['country_code'] ?? null;
        $isSaudi = $this->geoService->isAllowedLocation($location);
        $isAdmin = $this->geoService->isAdminIp($ip);
        $sessionToken = (string) $request->header('X-Session-Token', $request->input('session_id', ''));
        $sessionToken = strlen($sessionToken) <= 128 ? $sessionToken : '';

        // full: owner access, local: allowed public access, blog: foreign visitor.
        $accessScope = $isAdmin ? 'full' : ($isSaudi ? 'local' : 'blog');

        return response()->json([
            'success' => true,
            'is_saudi' => $isSaudi,
            'access_scope' => $accessScope,
            'customer_blocked' => CustomerBlock::matches($sessionToken),
            'country' => $location['country'] ?? null,
            'country_code' => $countryCode,
            'country_ar' => $this->geoService->getArabicCountryName($countryCode),
        ])->header('Cache-Control', 'private, no-store');
    }
}
