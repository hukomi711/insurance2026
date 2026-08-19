<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Traits\ResolvesRealIP;
use App\Services\GeoLocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminIpRestriction Middleware
 *
 * ميدلوير قديم تم تعطيله بالكامل.
 *
 * السبب: إزالة أي تقييد IP على مسارات الإدارة بشكل جذري.
 * لذلك أي مسار يستخدم alias `admin.ip` سيمر مباشرة بدون حظر.
 */
class AdminIpRestriction
{
    use ResolvesRealIP;
    public function __construct(
        protected GeoLocationService $geoService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
