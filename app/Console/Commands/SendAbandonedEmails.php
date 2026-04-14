<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedEmailJob;
use App\Models\CustomerProfile;
use App\Models\EmailLog;
use App\Models\FunnelEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SendAbandonedEmails extends Command
{
    /** @var string */
    protected $signature = 'emails:send-abandoned';

    /** @var string */
    protected $description = 'Find customers who abandoned the funnel with an email and queue recovery emails';

    /**
     * Per-step delay before sending (minutes after last activity).
     * Shorter for payment steps (high intent), longer for early steps.
     */
    private const STEP_DELAYS = [
        'compare'         => 5,
        'checkout'        => 5,
    ];

    public function handle(): int
    {
        $queued = 0;

        foreach (self::STEP_DELAYS as $step => $delayMinutes) {
            $threshold = now()->subMinutes($delayMinutes);

            // Find abandoned sessions WHERE:
            // 1. The last funnel event is "funnel_step_abandoned" on this step
            // 2. The customer has an email on file
            // 3. We haven't already sent an abandoned email for this session+step
            // 4. The abandonment happened after the delay threshold
            $candidates = DB::table('funnel_events as fe')
                ->join('customer_profiles as cp', 'cp.id', '=', 'fe.customer_profile_id')
                ->select([
                    'fe.session_id',
                    'fe.customer_profile_id',
                    'fe.step_name',
                    'cp.email',
                    'cp.full_name',
                ])
                ->where('fe.event_name', 'funnel_step_abandoned')
                ->where('fe.step_name', $step)
                ->where('fe.occurred_at', '>=', $threshold->copy()->subHours(24))
                ->where('fe.occurred_at', '<=', $threshold)
                ->whereNotNull('cp.email')
                ->where('cp.email', '!=', '')
                // No abandoned email already sent for this session + step
                ->whereNotExists(function ($q) use ($step) {
                    $q->select(DB::raw(1))
                      ->from('email_logs')
                      ->whereColumn('email_logs.session_id', 'fe.session_id')
                      ->where('email_logs.funnel_step', $step)
                      ->where('email_logs.type', EmailLog::TYPE_ABANDONED);
                })
                // Customer hasn't progressed past this step
                ->whereNotExists(function ($q) use ($step) {
                    $q->select(DB::raw(1))
                      ->from('funnel_events as newer')
                      ->whereColumn('newer.session_id', 'fe.session_id')
                      ->where('newer.event_name', 'funnel_step_viewed')
                      ->where('newer.step_order', '>', FunnelEvent::STEP_ORDER[$step]);
                })
                ->get();

            foreach ($candidates as $candidate) {
                // Decrypt email (column is now encrypted at rest)
                $email = $candidate->email;
                try {
                    $email = Crypt::decryptString($email);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    // Still plaintext (not yet migrated) — use as-is
                }

                // Daily rate limit check
                if (EmailLog::dailyLimitReached($email)) {
                    continue;
                }

                $subject = $this->getSubject($step);

                // Create pending log entry
                $log = EmailLog::create([
                    'customer_profile_id' => $candidate->customer_profile_id,
                    'email'               => $email,
                    'type'                => EmailLog::TYPE_ABANDONED,
                    'funnel_step'         => $step,
                    'subject'             => $subject,
                    'status'              => EmailLog::STATUS_PENDING,
                    'session_id'          => $candidate->session_id,
                ]);

                // Dispatch to Horizon queue
                SendAbandonedEmailJob::dispatch($log->id)->onQueue('emails');

                $queued++;
            }
        }

        $this->info("Queued {$queued} abandoned funnel email(s).");
        return self::SUCCESS;
    }

    private function getSubject(string $step): string
    {
        return match ($step) {
            'compare'  => 'خصم 30% على باقات التأمين - اختر باقتك الآن',
            default    => 'وثيقتك محجوزة - أكمل الدفع قبل انتهاء العرض',
        };
    }
}
