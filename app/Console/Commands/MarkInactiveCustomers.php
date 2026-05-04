<?php

namespace App\Console\Commands;

use App\Events\CustomerActivityUpdated;
use App\Models\CustomerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MarkInactiveCustomers extends Command
{
    /** @var string */
    protected $signature = 'customers:mark-inactive
                            {--minutes=5 : Minutes of inactivity before marking inactive}
                            {--seconds=0 : Seconds of inactivity (overrides --minutes if > 0)}';

    /** @var string */
    protected $description = 'Mark customers as inactive when they have no activity for a given period';

    public function handle(): int
    {
        $seconds = (int) $this->option('seconds');
        $minutes = (int) $this->option('minutes');

        $cutoff = $seconds > 0
            ? now()->subSeconds($seconds)
            : now()->subMinutes($minutes);

        // Fetch the customers first so we can broadcast events after updating
        $staleCustomers = CustomerProfile::where('is_active', true)
            ->where('last_activity_at', '<', $cutoff)
            ->get(['id', 'ip_address', 'current_page']);

        if ($staleCustomers->isEmpty()) {
            $this->info('No stale customers to mark inactive.');
            return self::SUCCESS;
        }

        // Redis-first heartbeat: visitors who keep heartbeating on the same page
        // do NOT update last_activity_at (we deliberately avoid the DB write).
        // Their liveness lives in Cache key visitor:last_seen:{ip}. Filter those
        // out so we don't falsely flip them to inactive.
        $stillLiveIds = [];
        foreach ($staleCustomers as $customer) {
            if (! empty($customer->ip_address)
                && Cache::has("visitor:last_seen:{$customer->ip_address}")) {
                $stillLiveIds[] = $customer->id;
            }
        }

        $staleCustomers = $staleCustomers->reject(
            fn ($c) => in_array($c->id, $stillLiveIds, true)
        );

        if ($staleCustomers->isEmpty()) {
            $this->info('All stale customers are still live in Redis cache.');
            return self::SUCCESS;
        }

        // Bulk update — only the truly stale (not in Redis cache)
        $idsToFlip = $staleCustomers->pluck('id')->all();
        CustomerProfile::whereIn('id', $idsToFlip)
            ->update(['is_active' => false]);

        // Broadcast inactivity event for each customer so the dashboard updates in real-time
        foreach ($staleCustomers as $customer) {
            try {
                broadcast(new CustomerActivityUpdated(
                    customerId:  $customer->id,
                    ipAddress:   $customer->ip_address ?? '',
                    currentPage: $customer->current_page,
                    isActive:    false,
                    activityType: 'inactive',
                ));
            } catch (\Throwable $e) {
                $this->warn("Failed to broadcast for customer #{$customer->id}: {$e->getMessage()}");
            }
        }

        $count = $staleCustomers->count();
        $label = $seconds > 0 ? "{$seconds} seconds" : "{$minutes} minutes";
        $this->info("Marked {$count} customer(s) as inactive (no activity for {$label}).");

        return self::SUCCESS;
    }
}
