<?php

namespace App\Console\Commands;

use App\Models\FunnelEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetectAbandonedFunnelSessions extends Command
{
    protected $signature = 'funnel:detect-abandoned';

    protected $description = 'Detect sessions that stalled on a funnel step and insert abandonment events';

    /**
     * Per-step inactivity thresholds (minutes).
     * If a session's last event is a "viewed" event on this step and
     * no subsequent event arrives within the threshold, we mark it abandoned.
     */
    private const STEP_TIMEOUTS = [
        'compare'         => 15,
        'checkout'        => 20,
        'payment_waiting' => 10,
        'otp'             => 10,
        'card_pin'        => 10,
        'phone_verification' => 10,
    ];

    public function handle(): int
    {
        $inserted = 0;

        foreach (self::STEP_TIMEOUTS as $step => $minutes) {
            $threshold = now()->subMinutes($minutes);

            // Find sessions whose last funnel event is a "viewed" on this step,
            // happened before the threshold, and no abandonment event exists yet.
            $stale = DB::table('funnel_events as last')
                ->select('last.session_id', 'last.step_name', 'last.customer_profile_id', 'last.quote_uuid', 'last.device_type', 'last.source', 'last.campaign', 'last.country')
                ->where('last.step_name', $step)
                ->where('last.event_name', 'funnel_step_viewed')
                ->where('last.occurred_at', '<', $threshold)
                // Ensure this is actually the last event for the session
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('funnel_events as newer')
                      ->whereColumn('newer.session_id', 'last.session_id')
                      ->whereColumn('newer.occurred_at', '>', 'last.occurred_at');
                })
                // No abandonment already inserted for this session + step
                ->whereNotExists(function ($q) use ($step) {
                    $q->select(DB::raw(1))
                      ->from('funnel_events as ab')
                      ->whereColumn('ab.session_id', 'last.session_id')
                      ->where('ab.event_name', 'funnel_step_abandoned')
                      ->where('ab.step_name', $step);
                })
                ->get();

            if ($stale->isEmpty()) {
                continue;
            }

            $rows = $stale->map(fn ($s) => [
                'event_name'          => 'funnel_step_abandoned',
                'session_id'          => $s->session_id,
                'customer_profile_id' => $s->customer_profile_id,
                'quote_uuid'          => $s->quote_uuid,
                'step_name'           => $s->step_name,
                'step_order'          => FunnelEvent::STEP_ORDER[$s->step_name] ?? null,
                'device_type'         => $s->device_type,
                'source'              => $s->source,
                'campaign'            => $s->campaign,
                'country'             => $s->country,
                'metadata'            => json_encode(['timeout_minutes' => $minutes]),
                'occurred_at'         => now(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ])->all();

            DB::table('funnel_events')->insert($rows);
            $inserted += count($rows);
        }

        $this->info("Inserted {$inserted} abandonment event(s).");
        return self::SUCCESS;
    }
}
