<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuoteMonitorController extends Controller
{
    /**
     * Live sessions overview for admin dashboard
     * GET /api/admin/quotes/live
     */
    public function live(Request $request): JsonResponse
    {
        $sessions = QuoteSession::active()
            ->where('last_heartbeat_at', '>=', now()->subMinutes(5))
            ->orderByDesc('last_heartbeat_at')
            ->get()
            ->map(fn($s) => $s->toMonitorFormat());

        return response()->json([
            'live_count' => $sessions->count(),
            'sessions' => $sessions,
        ]);
    }

    /**
     * All sessions with filtering + pagination
     * GET /api/admin/quotes
     */
    public function index(Request $request): JsonResponse
    {
        $query = QuoteSession::query();

        // Filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($step = $request->input('step')) {
            $query->where('current_step', $step);
        }
        if ($ip = $request->input('ip')) {
            $query->where('customer_ip', 'like', "%{$ip}%");
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $sessions = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($sessions);
    }

    /**
     * Single session detail
     * GET /api/admin/quotes/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $session = QuoteSession::where('uuid', $uuid)
            ->with(['stepLogs', 'heartbeats' => fn($q) => $q->latest('pinged_at')->limit(50)])
            ->firstOrFail();

        return response()->json($session->toMonitorFormat());
    }

    /**
     * Analytics & aggregated stats
     * GET /api/admin/quotes/analytics
     *
     * Cached for 2 minutes based on date range.
     */
    public function analytics(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $cacheKey = "admin:quote_analytics:{$from}:{$to}";

        $data = Cache::remember($cacheKey, 120, function () use ($from, $to) {
            // Single query for overview stats (was 4 separate COUNT queries)
            $overview = QuoteSession::whereBetween('created_at', [$from, $to])
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                    DB::raw("SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned"),
                ])
                ->first();

            $total     = (int) $overview->total;
            $completed = (int) $overview->completed;
            $abandoned = (int) $overview->abandoned;
            $active = QuoteSession::active()->count();

            $abandonmentByStep = QuoteSession::abandoned()
                ->whereBetween('created_at', [$from, $to])
                ->select('current_step', DB::raw('COUNT(*) as count'))
                ->groupBy('current_step')
                ->pluck('count', 'current_step');

            $avgDuration = QuoteSession::completed()
                ->whereBetween('created_at', [$from, $to])
                ->avg('total_duration_seconds') ?? 0;

            $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            $driver = DB::getDriverName();
            $dateExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m-%d', created_at)"
                : 'DATE(created_at)';

            $dailyTrend = QuoteSession::whereBetween('created_at', [now()->subDays(7), now()])
                ->select(
                    DB::raw("$dateExpr as date"),
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                    DB::raw("SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned"),
                )
                ->groupBy(DB::raw($dateExpr))
                ->orderBy('date')
                ->get();

            $deviceBreakdown = QuoteSession::whereBetween('created_at', [$from, $to])
                ->select('device_type', DB::raw('COUNT(*) as count'))
                ->groupBy('device_type')
                ->pluck('count', 'device_type');

            $stepFunnel = [];
            $stepMap = QuoteSession::stepCompletionMap();
            // Single query instead of N separate count() calls:
            // Fetch counts for each completion threshold in one pass.
            $thresholds = array_values($stepMap);
            $funnelCounts = QuoteSession::whereBetween('created_at', [$from, $to])
                ->select(
                    DB::raw(implode(', ', array_map(
                        fn($pct) => "SUM(CASE WHEN completion_percentage >= {$pct} THEN 1 ELSE 0 END) as pct_{$pct}",
                        $thresholds
                    )))
                )
                ->first();
            foreach ($stepMap as $step => $pct) {
                $stepFunnel[$step] = (int) ($funnelCounts->{"pct_{$pct}"} ?? 0);
            }

            return [
                'period' => ['from' => $from, 'to' => $to],
                'overview' => [
                    'total' => $total,
                    'completed' => $completed,
                    'abandoned' => $abandoned,
                    'active_now' => $active,
                    'completion_rate' => $completionRate,
                    'avg_duration_seconds' => round($avgDuration),
                ],
                'abandonment_by_step' => $abandonmentByStep,
                'daily_trend' => $dailyTrend,
                'device_breakdown' => $deviceBreakdown,
                'step_funnel' => $stepFunnel,
            ];
        });

        return response()->json($data);
    }
}
