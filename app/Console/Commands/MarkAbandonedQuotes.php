<?php

namespace App\Console\Commands;

use App\Models\QuoteSession;
use App\Models\QuoteHeartbeat;
use Illuminate\Console\Command;

class MarkAbandonedQuotes extends Command
{
    /** @var string */
    protected $signature = 'quotes:mark-abandoned
                            {--minutes=5 : Minutes of inactivity before marking abandoned}
                            {--cleanup-heartbeats : Also delete heartbeats older than 24h}';

    /** @var string */
    protected $description = 'Mark stale quote sessions as abandoned and optionally clean up old heartbeats';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        // Find stale active sessions
        $staleSessions = QuoteSession::stale($minutes)->get();
        $count = $staleSessions->count();

        if ($count === 0) {
            $this->info('No stale sessions found.');
        } else {
            foreach ($staleSessions as $session) {
                $session->markAbandoned();
            }
            $this->info("Marked {$count} session(s) as abandoned.");
        }

        // Optional: clean up old heartbeats to save space
        if ($this->option('cleanup-heartbeats')) {
            $deleted = QuoteHeartbeat::where('pinged_at', '<', now()->subDay())->delete();
            $this->info("Deleted {$deleted} old heartbeat record(s).");
        }

        return self::SUCCESS;
    }
}
