<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEmailStatsController extends Controller
{
    /**
     * GET /api/admin/email-stats
     * Overview statistics for the email marketing system.
     */
    public function index(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        $since = now()->subDays($days);

        $stats = EmailLog::where('created_at', '>=', $since)
            ->selectRaw("
                COUNT(*)                                           as total,
                SUM(CASE WHEN status = 'sent'   THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked
            ")
            ->first();

        $openRate  = $stats->sent > 0 ? round(($stats->opened / $stats->sent) * 100, 1) : 0;
        $clickRate = $stats->opened > 0 ? round(($stats->clicked / $stats->opened) * 100, 1) : 0;

        // Per-step breakdown
        $byStep = EmailLog::where('created_at', '>=', $since)
            ->where('type', EmailLog::TYPE_ABANDONED)
            ->groupBy('funnel_step')
            ->selectRaw("
                funnel_step,
                COUNT(*)                                               as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END)       as sent,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked
            ")
            ->get()
            ->keyBy('funnel_step');

        // Daily trend (last N days)
        $daily = EmailLog::where('created_at', '>=', $since)
            ->where('status', EmailLog::STATUS_SENT)
            ->groupBy('date')
            ->selectRaw("
                DATE(sent_at) as date,
                COUNT(*) as sent,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked
            ")
            ->orderBy('date')
            ->get();

        return response()->json([
            'period_days' => $days,
            'summary'     => [
                'total'      => (int) $stats->total,
                'sent'       => (int) $stats->sent,
                'failed'     => (int) $stats->failed,
                'opened'     => (int) $stats->opened,
                'clicked'    => (int) $stats->clicked,
                'open_rate'  => $openRate,
                'click_rate' => $clickRate,
            ],
            'by_step' => $byStep,
            'daily'   => $daily,
        ]);
    }
}
