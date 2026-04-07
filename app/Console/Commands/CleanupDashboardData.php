<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use App\Models\PaymentCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDashboardData extends Command
{
    protected $signature = 'dashboard:cleanup {--dry-run : Show what would be cleaned without making changes}';

    protected $description = 'One-time cleanup: remove bot profiles, sanitize current_page, remove duplicate cards';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // 1. Delete bot profiles (Googlebot IPs) that have no payment cards
        $botProfiles = CustomerProfile::where(function ($q) {
            $q->where('ip_address', 'like', '66.249.%')
              ->orWhere('ip_address', 'like', '66.102.%')
              ->orWhere('ip_address', 'like', '66.118.%');
        })->doesntHave('paymentCards')->get();

        $this->info("Bot profiles to delete: {$botProfiles->count()}");
        if (! $dryRun && $botProfiles->isNotEmpty()) {
            CustomerProfile::whereIn('id', $botProfiles->pluck('id'))->delete();
            $this->info('  ✓ Deleted.');
        }

        // 2. Sanitize current_page — set malicious paths to null
        $malicious = CustomerProfile::where(function ($q) {
            $q->where('current_page', 'like', '%etc/passwd%')
              ->orWhere('current_page', 'like', '%wp-login%')
              ->orWhere('current_page', 'like', '%wp-admin%')
              ->orWhere('current_page', 'like', '%.env%')
              ->orWhere('current_page', 'like', '%phpmyadmin%')
              ->orWhere('current_page', 'like', '%xmlrpc%')
              ->orWhere('current_page', 'like', '%cgi-bin%');
        })->get();

        $this->info("Profiles with malicious current_page: {$malicious->count()}");
        if (! $dryRun && $malicious->isNotEmpty()) {
            CustomerProfile::whereIn('id', $malicious->pluck('id'))
                ->update(['current_page' => null]);
            $this->info('  ✓ Sanitized.');
        }

        // 3. Remove duplicate payment cards (keep latest per profile + last4 combo)
        $duplicates = DB::select("
            SELECT pc.id
            FROM payment_cards pc
            INNER JOIN (
                SELECT customer_profile_id, last4, MAX(id) as keep_id
                FROM payment_cards
                WHERE last4 IS NOT NULL AND last4 != ''
                GROUP BY customer_profile_id, last4
                HAVING COUNT(*) > 1
            ) dups ON pc.customer_profile_id = dups.customer_profile_id
                  AND pc.last4 = dups.last4
                  AND pc.id != dups.keep_id
        ");

        $duplicateIds = collect($duplicates)->pluck('id');
        $this->info("Duplicate payment cards to remove: {$duplicateIds->count()}");
        if (! $dryRun && $duplicateIds->isNotEmpty()) {
            PaymentCard::whereIn('id', $duplicateIds)->delete();
            $this->info('  ✓ Removed.');
        }

        // 4. Re-activate customers with recent activity (fix stale is_active from 15s scheduler)
        $recentlyActive = CustomerProfile::where('is_active', false)
            ->where('last_activity_at', '>=', now()->subMinutes(3))
            ->count();

        $this->info("Profiles to re-activate (active in last 3 min): {$recentlyActive}");
        if (! $dryRun && $recentlyActive > 0) {
            CustomerProfile::where('is_active', false)
                ->where('last_activity_at', '>=', now()->subMinutes(3))
                ->update(['is_active' => true]);
            $this->info('  ✓ Re-activated.');
        }

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes were made.');
        } else {
            $this->info('✅ Cleanup complete.');
        }

        return self::SUCCESS;
    }
}
