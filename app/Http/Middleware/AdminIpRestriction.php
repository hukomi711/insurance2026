<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Traits\ResolvesRealIP;
use App\Services\GeoLocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminIpRestriction Middleware
 *
 * يقيّد الوصول إلى مسارات الإدارة (login + dashboard + admin API)
 * على عناوين IP محددة في ADMIN_ALLOWED_IPS فقط.
 *
 * Fail-Closed: إذا لم تُحدد قائمة IPs → يُحظر الجميع.
 */
class AdminIpRestriction
{
    use ResolvesRealIP;
    public function __construct(
        protected GeoLocationService $geoService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $this->getRealIP($request);

        if ($this->geoService->isAdminIp($ip)) {
            return $next($request);
        }

        // تسجيل المحاولة غير المصرح بها
        Log::warning('AdminIpRestriction: unauthorized admin access attempt', [
            'ip' => $ip,
            'path' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);

        // إرجاع 403 JSON لطلبات API
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول.',
            ], 403);
        }

        // إرجاع 404 لطلبات الويب (لإخفاء وجود الصفحة)
        abort(404);
    }
}
