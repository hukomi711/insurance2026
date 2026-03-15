<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only Cloudflare + loopback reverse-proxy headers.
        // Cloudflare IPv4: https://www.cloudflare.com/ips-v4
        // Cloudflare IPv6: https://www.cloudflare.com/ips-v6
        // ⚠️  If you add another LB/proxy, append its CIDR here — never use '*'.
        $middleware->trustProxies(
            at: [
                '127.0.0.1',
                '::1',
                // Docker bridge networks
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                // Cloudflare IPv4
                '173.245.48.0/20',
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                '162.158.0.0/15',
                '104.16.0.0/13',
                '104.24.0.0/14',
                '172.64.0.0/13',
                '131.0.72.0/22',
                // Cloudflare IPv6
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32',
                '2a06:98c0::/29',
                '2c0f:f248::/32',
            ],
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\TrackCustomerActivity::class,
            \App\Http\Middleware\CountryRestriction::class,
        ]);

        // Performance monitoring — logs slow requests, N+1 queries, large payloads
        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\PerformanceMonitor::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'admin.ip' => \App\Http\Middleware\AdminIpRestriction::class,
            'geo.api' => \App\Http\Middleware\ApiGeoRestriction::class,
            'status.sig' => \App\Http\Middleware\VerifyStatusSignature::class,
        ]);

        // For API requests, don't try to redirect to a 'login' route
        // (which doesn't exist). Return null so AuthenticationException
        // is thrown and our custom exception renderer returns JSON 401.
        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('api/*') ? null : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON 401 for unauthenticated API requests
        // instead of redirecting to a non-existent 'login' route
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح. يرجى تسجيل الدخول.',
                ], 401);
            }
        });

        // Return JSON for API 404s instead of HTML
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        // Return JSON 419 for CSRF token mismatch on API/AJAX requests
        // (happens when the session expires; the frontend can refresh the cookie and retry)
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة.',
                ], 419);
            }
        });

        // Return JSON for rate-limited API requests
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                ], 429);
            }
        });

        // Don't report common noise exceptions (keep 500s reportable)
        $exceptions->dontReport([
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        ]);
    })->create();
