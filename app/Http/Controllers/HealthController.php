<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * GET /api/health — basic liveness + dependency checks.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => 'ok',
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        // On shared hosting without Redis, exclude it from health gate
        $filtered = array_filter($checks, fn ($v) => $v !== 'not_configured');

        return $this->respond($filtered);
    }

    /**
     * GET /api/health/realtime — broadcasting / Reverb readiness.
     */
    public function realtime(): JsonResponse
    {
        $redis = $this->checkRedis();

        $broadcastingDriver = filled(config('broadcasting.default'))
            ? 'ok'
            : 'unhealthy';

        $reverbHost = filled(config('reverb.servers.reverb.host'))
            ? 'ok'
            : 'unhealthy';

        $reverbPort = filled(config('reverb.servers.reverb.port'))
            ? 'ok'
            : 'unhealthy';

        $checks = [
            'broadcasting_driver' => $broadcastingDriver,
            'reverb_host' => $reverbHost,
            'reverb_port' => $reverbPort,
            'redis' => $redis,
        ];

        return $this->respond($checks);
    }

    /**
     * GET /api/health/queues — Horizon / queue worker status.
     */
    public function queues(): JsonResponse
    {
        $redis = $this->checkRedis();

        $queueDriver = filled(config('queue.default'))
            ? 'ok'
            : 'unhealthy';

        $failedJobs = 'ok';
        try {
            DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $failedJobs = 'unhealthy';
        }

        $horizonStatus = 'unhealthy';
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('horizon:status');
            $horizonStatus = $exitCode === 0 ? 'ok' : 'unhealthy';
        } catch (\Throwable) {
            // already unhealthy
        }

        $checks = [
            'queue_driver' => $queueDriver,
            'redis' => $redis,
            'horizon' => $horizonStatus,
            'failed_jobs_table' => $failedJobs,
        ];

        return $this->respond($checks);
    }

    // ─── Shared helpers ─────────────────────────────────────────────

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Throwable $e) {
            Log::warning('[Health] Database check failed', ['error' => $e->getMessage()]);
            return 'unhealthy';
        }
    }

    private function checkRedis(): string
    {
        // Skip Redis check entirely when not configured as the cache/session/queue driver
        $usesRedis = in_array('redis', [
            config('cache.default'),
            config('session.driver'),
            config('queue.default'),
            config('broadcasting.default'),
        ], true);

        if (! $usesRedis) {
            return 'not_configured';
        }

        try {
            Redis::connection()->ping();

            return 'ok';
        } catch (\Throwable $e) {
            Log::warning('[Health] Redis check failed', ['error' => $e->getMessage()]);
            return 'unhealthy';
        }
    }

    private function respond(array $checks): JsonResponse
    {
        $healthy = ! in_array('unhealthy', $checks, true);

        return response()->json([
            'ok' => $healthy,
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
