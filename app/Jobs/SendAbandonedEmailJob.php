<?php

namespace App\Jobs;

use App\Mail\AbandonedFunnelEmail;
use App\Models\EmailLog;
use App\Models\FunnelEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        protected int $emailLogId,
    ) {}

    public function handle(): void
    {
        $log = EmailLog::find($this->emailLogId);

        if (! $log || $log->status !== EmailLog::STATUS_PENDING) {
            return;
        }

        // ── Guard: customer progressed past the abandoned step ──
        if ($log->session_id && $log->funnel_step) {
            $currentStepOrder = FunnelEvent::STEP_ORDER[$log->funnel_step] ?? 0;

            $progressedPast = FunnelEvent::where('session_id', $log->session_id)
                ->where('event_name', 'funnel_step_viewed')
                ->where('step_order', '>', $currentStepOrder)
                ->exists();

            if ($progressedPast) {
                $log->update(['status' => 'skipped', 'failure_reason' => 'Customer progressed past step']);
                return;
            }
        }

        // ── Guard: daily rate limit ──
        if (EmailLog::dailyLimitReached($log->email)) {
            $log->update(['status' => 'skipped', 'failure_reason' => 'Daily limit reached']);
            return;
        }

        // ── Send the email ──
        try {
            Mail::to($log->email)->send(new AbandonedFunnelEmail($log->funnel_step, $log));

            $log->update([
                'status'  => EmailLog::STATUS_SENT,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AbandonedEmail failed', [
                'email_log_id' => $log->id,
                'email'        => $log->email,
                'error'        => $e->getMessage(),
            ]);

            $log->update([
                'status'         => EmailLog::STATUS_FAILED,
                'failure_reason' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }
    }
}
