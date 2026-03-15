<?php

namespace App\Console\Commands;

use App\Events\CustomerActivityUpdated;
use App\Models\CustomerProfile;
use Illuminate\Console\Command;

class MarkInactiveCustomers extends Command
{
    protected $signature = 'customers:mark-inactive
                            {--minutes=5 : Minutes of inactivity before marking inactive}
                            {--seconds=0 : Seconds of inactivity (overrides --minutes if > 0)}';

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

        // Bulk update
        CustomerProfile::where('is_active', true)
            ->where('last_activity_at', '<', $cutoff)
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
