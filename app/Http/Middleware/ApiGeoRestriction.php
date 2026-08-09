<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Traits\ResolvesRealIP;
use App\Services\GeoLocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiGeoRestriction Middleware
 *
 * يقيّد طلبات API العامة (التأمين، الدفع، OTP...) على الزوار من الدول المسموح بها فقط.
 * لا يُطبق على مسارات الإدارة (لها middleware خاص) أو المسارات المستثناة.
 *
 * Fail-Open: إذا فشل تحديد الموقع → يُسمح بالطلب.
 */
class ApiGeoRestriction
{
    use ResolvesRealIP;

    /**
     * مسارات API المستثناة من التقييد الجغرافي
     */
    protected array $exemptPaths = [
        'api/geo/check',        // فحص الموقع الجغرافي (يحتاجه الـ frontend)
        'api/newsletter',       // اشتراك النشرة (زوار المدونة)
        'api/contact',          // نموذج التواصل
        'api/customer/*',       // تتبع الزوار وتحديد IP (يحتاجه الـ SPA لكل الزوار)
        'api/quote/*',          // تتبع جلسات التسعير دون حظر جغرافي
        'api/pricing/*',        // ✓ NEW: pricing constants sync (public pricing engine)
        'api/quotes/*',         // ✓ NEW: quote calculation & locking (public pricing)
        'api/analytics/*',      // ✓ NEW: funnel analytics tracking (all visitors)
    ];

    public function __construct(
        protected GeoLocationService $geoService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // التقييد الجغرافي معطّل
        if (! $this->geoService->isEnabled()) {
            return $next($request);
        }

        // المسارات المستثناة
        \Log::warning('GeoRestriction check', ['path' => $request->path(), 'patterns' => $this->exemptPaths]);
        foreach ($this->exemptPaths as $path) {
            if ($request->is($path)) {
                \Log::warning('GeoRestriction: exempt path allowed', [
                    'request_path' => $request->path(),
                    'matched_pattern' => $path,
                ]);
                return $next($request);
            }
        }
        \Log::warning('GeoRestriction: no pattern matched', ['path' => $request->path()]);

        // الأدمن المسجّل عبر Sanctum أو session → وصول كامل
        if ($request->user() || session('admin_authenticated')) {
            return $next($request);
        }

        $ip = $this->getRealIP($request);

        // IP المالك → وصول كامل لكل APIs بدون قيد جغرافي
        if ($this->geoService->isAdminIp($ip)) {
            return $next($request);
        }

        // السعودية (أو Fail-Open) → مسموح
        if ($this->geoService->isSaudiArabia($ip)) {
            return $next($request);
        }

        // خارج السعودية → حظر
        \Log::warning('GeoRestriction: blocked non-Saudi IP', [
            'ip' => $ip,
            'request_path' => $request->path(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'هذه الخدمة متاحة فقط داخل المملكة العربية السعودية.',
        ], 403);
    }
}
