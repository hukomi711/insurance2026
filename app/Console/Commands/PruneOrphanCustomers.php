<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use Illuminate\Console\Command;

/**
 * Hard-delete orphan customer profiles (no payment cards, no OTP codes, no orders)
 * older than N days. These are typically bot scans or visitors who bounced before
 * starting the funnel. Run nightly to keep customer_profiles lean.
 */
class PruneOrphanCustomers extends Command
{
    /** @var string */
    protected $signature = 'customers:prune-orphans
                            {--days=30 : Minimum age in days before an orphan is eligible for deletion}
                            {--dry-run : Show what would be deleted without making changes}';

    /** @var string */
    protected $description = 'Hard-delete orphan customer profiles (no cards, no OTPs, no orders) older than N days';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = CustomerProfile::query()
            ->where('created_at', '<', $cutoff)
            ->doesntHave('paymentCards')
            ->doesntHave('otpCodes')
            ->whereDoesntHave('orders');

        $count = $query->count();

        if ($dryRun) {
            $this->warn("[DRY RUN] Would delete {$count} orphan profile(s) older than {$days}d.");

            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info("No orphan profiles older than {$days}d to delete.");

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->select('id')->orderBy('id')->chunkById(1000, function ($rows) use (&$deleted) {
            $ids = $rows->pluck('id')->all();
            $deleted += CustomerProfile::whereIn('id', $ids)->delete();
        });

        $this->info("Deleted {$deleted} orphan profile(s).");

        return self::SUCCESS;
    }
}
