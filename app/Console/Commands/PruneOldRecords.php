<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOldRecords extends Command
{
    protected $signature = 'app:prune-old-records
                            {--dry-run : Show what would be pruned without executing}
                            {--tier=1,2,3 : Comma-separated tiers to run (1=ephemeral, 2=operational, 3=security)}
                            {--only= : Only process this specific table}
                            {--include-anonymize : Include Tier 4 customer profile anonymization}';

    protected $description = 'Tiered data retention: prune old records, scrub sensitive fields, and optionally anonymize inactive profiles';

    private int $chunkSize = 1000;

    private bool $dryRun = false;

    private array $tiers = [];

    private ?string $only = null;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $this->tiers = array_map('intval', explode(',', $this->option('tier')));
        $this->only = $this->option('only') ?: null;

        if ($this->dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $this->newLine();

        if (in_array(1, $this->tiers)) {
            $this->processTier1();
        }

        if (in_array(2, $this->tiers)) {
            $this->processTier2();
        }

        if (in_array(3, $this->tiers)) {
            $this->processTier3();
        }

        if ($this->option('include-anonymize')) {
            $this->processTier4();
        } elseif (in_array(4, $this->tiers)) {
            $this->warn('Tier 4 requires --include-anonymize flag. Skipping.');
        }

        $this->newLine();
        $this->info('Pruning complete.');

        return self::SUCCESS;
    }

    // ── Tier 1: Ephemeral Telemetry (Hard-Delete) ─────────────

    private function processTier1(): void
    {
        $this->info('── Tier 1: Ephemeral Telemetry ──');

        $this->pruneTable('quote_heartbeats', 'pinged_at', 3);
        $this->pruneTable('customer_activities', 'created_at', 30);
        $this->pruneTable('user_activities', 'created_at', 60);
        $this->pruneTable('login_attempts', 'created_at', 90);
    }

    // ── Tier 2: Operational Telemetry (Hard-Delete, Longer TTL) ──

    private function processTier2(): void
    {
        $this->info('── Tier 2: Operational Telemetry ──');

        $this->pruneTableWithStatus('quote_sessions', 'created_at', 90, ['abandoned', 'expired']);
        $this->pruneTableWithStatus('livechat_conversations', 'updated_at', 180, ['closed']);
        $this->pruneTableWithStatus('contact_submissions', 'created_at', 180, ['replied']);
    }

    // ── Tier 3: Security/PII Records (Scrub Sensitive Fields) ──

    private function processTier3(): void
    {
        $this->info('── Tier 3: Security/PII Scrubbing ──');

        $this->scrubTable('otp_codes', [
            'columns'     => ['code' => null, 'code_value' => null],
            'date_column' => 'created_at',
            'days'        => 7,
            'conditions'  => [['status', '!=', 'pending']],
            'null_check'  => 'code',
        ]);

        $this->scrubTable('payment_cards', [
            'columns'     => ['card_number' => null, 'cvv' => null],
            'date_column' => 'created_at',
            'days'        => 30,
            'conditions'  => [['status', '!=', 'pending']],
            'null_check'  => 'card_number',
        ]);

        $this->scrubTable('phone_verifications', [
            'columns'     => ['otp_code' => null],
            'date_column' => 'created_at',
            'days'        => 7,
            'null_check'  => 'otp_code',
        ]);

        $this->scrubTable('payment_requests', [
            'columns'     => ['gateway_response' => null],
            'date_column' => 'created_at',
            'days'        => 90,
            'null_check'  => 'gateway_response',
        ]);

        $this->expireStalePendingOtps();
    }

    // ── Tier 4: Anonymize Inactive Customer Profiles ──────────

    private function processTier4(): void
    {
        $this->info('── Tier 4: Customer Profile Anonymization ──');

        if (! $this->shouldProcess('customer_profiles')) {
            return;
        }

        $cutoff = now()->subDays(180);

        $query = DB::table('customer_profiles')
            ->whereNull('anonymized_at')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_activity_at', '<', $cutoff)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereNull('last_activity_at')
                         ->where('updated_at', '<', $cutoff);
                  });
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('orders')
                  ->whereColumn('orders.customer_profile_id', 'customer_profiles.id');
            })
            ;

        $count = $query->count();

        if ($this->dryRun) {
            $this->line("  [DRY RUN] customer_profiles: {$count} would be anonymized");
            return;
        }

        if ($count === 0) {
            $this->line('  customer_profiles: 0 to anonymize');
            return;
        }

        $piiColumns = [
            'full_name'                    => null,
            'phone_number'                 => null,
            'national_id'                  => null,
            'email'                        => null,
            'birth_date'                   => null,
            'birth_year'                   => null,
            'birth_month'                  => null,
            'nafath_username'              => null,
            'nafath_password'              => null,
            'nafath_verification_code'     => null,
            'additional_driver_name'       => null,
            'additional_driver_national_id' => null,
            'additional_driver_birth_date' => null,
            'anonymized_at'               => now(),
        ];

        $total = 0;

        (clone $query)->select('id')->orderBy('id')->chunk($this->chunkSize, function ($rows) use ($piiColumns, &$total) {
            $ids = $rows->pluck('id')->all();
            DB::table('customer_profiles')->whereIn('id', $ids)->update($piiColumns);
            $total += count($ids);
        });

        $this->line("  customer_profiles: {$total} anonymized");
    }

    // ── Helpers ────────────────────────────────────────────────

    private function pruneTable(string $table, string $dateColumn, int $days): void
    {
        if (! $this->shouldProcess($table)) {
            return;
        }

        $cutoff = now()->subDays($days);

        if ($this->dryRun) {
            $count = DB::table($table)->where($dateColumn, '<', $cutoff)->count();
            $this->line("  [DRY RUN] {$table}: {$count} records older than {$days}d");
            return;
        }

        $total = 0;

        do {
            $deleted = DB::table($table)
                ->where($dateColumn, '<', $cutoff)
                ->limit($this->chunkSize)
                ->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->line("  {$table}: {$total} deleted (>{$days}d)");
    }

    private function pruneTableWithStatus(string $table, string $dateColumn, int $days, array $statuses): void
    {
        if (! $this->shouldProcess($table)) {
            return;
        }

        $cutoff = now()->subDays($days);
        $statusLabel = implode(',', $statuses);

        if ($this->dryRun) {
            $count = DB::table($table)
                ->whereIn('status', $statuses)
                ->where($dateColumn, '<', $cutoff)
                ->count();
            $this->line("  [DRY RUN] {$table}: {$count} records (status: {$statusLabel}, >{$days}d)");
            return;
        }

        $total = 0;

        do {
            $deleted = DB::table($table)
                ->whereIn('status', $statuses)
                ->where($dateColumn, '<', $cutoff)
                ->limit($this->chunkSize)
                ->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->line("  {$table}: {$total} deleted (status: {$statusLabel}, >{$days}d)");
    }

    private function scrubTable(string $table, array $config): void
    {
        if (! $this->shouldProcess($table)) {
            return;
        }

        $cutoff = now()->subDays($config['days']);

        $query = DB::table($table)
            ->where($config['date_column'], '<', $cutoff)
            ->whereNotNull($config['null_check']);

        foreach ($config['conditions'] ?? [] as [$col, $op, $val]) {
            $query->where($col, $op, $val);
        }

        if ($this->dryRun) {
            $count = (clone $query)->count();
            $this->line("  [DRY RUN] {$table}: {$count} records to scrub (>{$config['days']}d)");
            return;
        }

        $total = 0;

        (clone $query)->select('id')->orderBy('id')->chunk($this->chunkSize, function ($rows) use ($table, $config, &$total) {
            $ids = $rows->pluck('id')->all();
            DB::table($table)->whereIn('id', $ids)->update($config['columns']);
            $total += count($ids);
        });

        $this->line("  {$table}: {$total} scrubbed (>{$config['days']}d)");
    }

    private function expireStalePendingOtps(): void
    {
        if (! $this->shouldProcess('otp_codes')) {
            return;
        }

        $cutoff = now()->subHours(24);

        if ($this->dryRun) {
            $count = DB::table('otp_codes')
                ->where('status', 'pending')
                ->where('created_at', '<', $cutoff)
                ->count();
            $this->line("  [DRY RUN] otp_codes (stale pending): {$count} would be expired");
            return;
        }

        $expired = DB::table('otp_codes')
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->update(['status' => 'expired']);

        $this->line("  otp_codes: {$expired} stale pending expired (>24h)");
    }

    private function shouldProcess(string $table): bool
    {
        return $this->only === null || $this->only === $table;
    }
}
