<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Lightweight performance monitoring for production.
 *
 * Logs slow requests (>1s) and endpoints with high query counts (>20).
 * Zero overhead when requests are fast — only triggers logging on threshold breach.
 *
 * Register in bootstrap/app.php:
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->append(\App\Http\Middleware\PerformanceMonitor::class);
 *   })
 *
 * Or apply to specific route groups:
 *   Route::middleware('perf.monitor')->group(...)
 */
class PerformanceMonitor
{
    /** Maximum request duration (ms) before logging a warning. */
    private const SLOW_REQUEST_MS = 1000;

    /** Maximum query count per request before logging a warning. */
    private const MAX_QUERIES = 20;

    /** Maximum response payload size (bytes) before logging a warning. */
    private const MAX_RESPONSE_BYTES = 512_000; // 500 KB

    public function handle(Request $request, Closure $next): Response
    {
        // Skip non-API and non-admin routes for minimal overhead
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $startTime = microtime(true);
        $queryCount = 0;
        $queryTimeMs = 0;

        // Listen to DB queries (only in non-testing)
        if (! app()->runningUnitTests()) {
            DB::listen(function ($query) use (&$queryCount, &$queryTimeMs) {
                $queryCount++;
                $queryTimeMs += $query->time;
            });
        }

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 1);
        $responseSize = strlen($response->getContent() ?? '');

        // ── Log slow requests ──
        if ($durationMs > self::SLOW_REQUEST_MS) {
            $this->logWarning('[PERF] Slow request', [
                'method'       => $request->method(),
                'uri'          => $request->getRequestUri(),
                'duration_ms'  => $durationMs,
                'queries'      => $queryCount,
                'query_time_ms'=> round($queryTimeMs, 1),
                'response_kb'  => round($responseSize / 1024, 1),
                'ip'           => $request->ip(),
            ]);
        }

        // ── Log high query count (N+1 detection) ──
        if ($queryCount > self::MAX_QUERIES) {
            $this->logWarning('[PERF] High query count — possible N+1', [
                'method'      => $request->method(),
                'uri'         => $request->getRequestUri(),
                'queries'     => $queryCount,
                'query_time_ms' => round($queryTimeMs, 1),
                'duration_ms' => $durationMs,
            ]);
        }

        // ── Log oversized responses ──
        if ($responseSize > self::MAX_RESPONSE_BYTES) {
            $this->logWarning('[PERF] Large response payload', [
                'method'      => $request->method(),
                'uri'         => $request->getRequestUri(),
                'response_kb' => round($responseSize / 1024, 1),
                'queries'     => $queryCount,
                'duration_ms' => $durationMs,
            ]);
        }

        return $response;
    }

    /** Monitoring must never turn a successful application response into a 500. */
    private function logWarning(string $message, array $context): void
    {
        try {
            Log::channel('daily')->warning($message, $context);
        } catch (Throwable) {
            // A deployment-time ownership mistake must not break the request.
        }
    }
}
