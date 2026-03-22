<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFunnelEventRequest;
use App\Models\CustomerProfile;
use App\Models\FunnelEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FunnelAnalyticsController extends Controller
{
    /**
     * Record a funnel event.
     *
     * POST /api/analytics/funnel-event
     *
     * Lightweight fire-and-forget endpoint. Server enriches with IP, UA,
     * geo, customer_profile_id, and server timestamp.
     */
    public function store(StoreFunnelEventRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ip        = $request->ip();
        $sessionId = $request->header('X-Session-Token', '');
        $ua        = $request->userAgent() ?? '';

        // ── Resolve customer profile (match existing, don't create) ──
        $customerId = null;
        if ($sessionId) {
            $customerId = CustomerProfile::where('session_id', $sessionId)
                ->value('id');
        }
        if (! $customerId && $ip) {
            $customerId = CustomerProfile::where('ip_address', $ip)
                ->value('id');
        }

        // ── Resolve country from existing profile or geo middleware ──
        $country = null;
        if ($customerId) {
            $country = CustomerProfile::where('id', $customerId)->value('country');
        }

        // ── Resolve returning user ──
        $isReturning = false;
        if ($customerId) {
            $isReturning = FunnelEvent::where('customer_profile_id', $customerId)
                ->where('event_name', 'funnel_step_viewed')
                ->where('occurred_at', '<', now()->subHours(1))
                ->exists();
        }

        // ── Resolve step_order from step_name ──
        $stepOrder = null;
        if (! empty($validated['step_name'])) {
            $stepOrder = FunnelEvent::STEP_ORDER[$validated['step_name']] ?? null;
        }

        FunnelEvent::create([
            'event_name'          => $validated['event_name'],
            'session_id'          => $sessionId,
            'customer_profile_id' => $customerId,
            'quote_uuid'          => $validated['quote_uuid'] ?? null,
            'step_name'           => $validated['step_name'] ?? null,
            'step_order'          => $stepOrder,
            'previous_step'       => $validated['previous_step'] ?? null,
            'device_type'         => $validated['device_type'] ?? null,
            'source'              => $validated['source'] ?? null,
            'campaign'            => $validated['campaign'] ?? null,
            'country'             => $country,
            'is_returning_user'   => $isReturning,
            'elapsed_seconds'     => $validated['elapsed_seconds'] ?? null,
            'metadata'            => $validated['metadata'] ?? null,
            'ip_address'          => $ip,
            'user_agent'          => mb_substr($ua, 0, 512),
            'occurred_at'         => now(),
        ]);

        return response()->json(['success' => true], 201);
    }

    /**
     * Funnel conversion report.
     *
     * GET /api/admin/funnel/report
     *
     * Query params:
     *   from        — Start date (Y-m-d), default: 7 days ago
     *   to          — End date (Y-m-d), default: today
     *   device_type — Filter: mobile|desktop
     *   source      — Filter: UTM source
     *   campaign    — Filter: UTM campaign
     */
    public function report(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(7)->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $query = FunnelEvent::whereBetween('occurred_at', [
            $from . ' 00:00:00',
            $to   . ' 23:59:59',
        ]);

        if ($device = $request->input('device_type')) {
            $query->where('device_type', $device);
        }
        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }
        if ($campaign = $request->input('campaign')) {
            $query->where('campaign', $campaign);
        }

        // ── 1. Step views & completions count ──
        $stepViews = (clone $query)
            ->where('event_name', 'funnel_step_viewed')
            ->select('step_name', DB::raw('COUNT(*) as views'))
            ->groupBy('step_name')
            ->pluck('views', 'step_name');

        $stepCompletions = (clone $query)
            ->where('event_name', 'funnel_step_completed')
            ->select('step_name', DB::raw('COUNT(*) as completions'))
            ->groupBy('step_name')
            ->pluck('completions', 'step_name');

        $stepAbandoned = (clone $query)
            ->where('event_name', 'funnel_step_abandoned')
            ->select('step_name', DB::raw('COUNT(*) as abandoned'))
            ->groupBy('step_name')
            ->pluck('abandoned', 'step_name');

        // Build ordered funnel
        $funnel = [];
        foreach (FunnelEvent::STEP_ORDER as $step => $order) {
            $views       = $stepViews[$step] ?? 0;
            $completions = $stepCompletions[$step] ?? 0;
            $abandoned   = $stepAbandoned[$step] ?? 0;

            $funnel[] = [
                'step'            => $step,
                'order'           => $order,
                'views'           => $views,
                'completions'     => $completions,
                'abandoned'       => $abandoned,
                'conversion_rate' => $views > 0
                    ? round($completions / $views * 100, 1)
                    : 0,
            ];
        }

        // ── 2. OTP breakdown ──
        $otp = [
            'requested' => (clone $query)->where('event_name', 'otp_requested')->count(),
            'resent'    => (clone $query)->where('event_name', 'otp_resent')->count(),
            'expired'   => (clone $query)->where('event_name', 'otp_expired')->count(),
            'verified'  => (clone $query)->where('event_name', 'otp_verified')->count(),
        ];
        $otp['success_rate'] = $otp['requested'] > 0
            ? round($otp['verified'] / $otp['requested'] * 100, 1)
            : 0;

        // ── 3. Overall conversion (compare → confirmation) ──
        $totalCompare      = $stepViews['compare'] ?? 0;
        $totalConfirmation = $stepViews['confirmation'] ?? 0;
        $overallConversion = $totalCompare > 0
            ? round($totalConfirmation / $totalCompare * 100, 1)
            : 0;

        // ── 4. Median elapsed time per step (seconds from funnel start) ──
        $medianTimes = (clone $query)
            ->where('event_name', 'funnel_step_viewed')
            ->whereNotNull('elapsed_seconds')
            ->select('step_name', DB::raw('ROUND(AVG(elapsed_seconds)) as avg_seconds'))
            ->groupBy('step_name')
            ->pluck('avg_seconds', 'step_name');

        // ── 5. Daily summary (last N days) ──
        $daily = (clone $query)
            ->where('event_name', 'funnel_step_viewed')
            ->select(
                DB::raw('DATE(occurred_at) as day'),
                DB::raw('COUNT(DISTINCT session_id) as unique_sessions'),
                DB::raw('COUNT(*) as total_events')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // ── 6. Device breakdown ──
        $deviceBreakdown = (clone $query)
            ->where('event_name', 'funnel_step_viewed')
            ->where('step_name', 'compare')
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type');

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'funnel' => $funnel,
            'otp'    => $otp,
            'overall_conversion' => $overallConversion,
            'median_elapsed_seconds' => $medianTimes,
            'daily' => $daily,
            'device_breakdown' => $deviceBreakdown,
        ]);
    }
}
