<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Merge duplicate customer profiles into one card per customer.
 *
 * Strategy:
 *   1. Group by national_id (where not null) → merge into the most complete record
 *   2. Group remaining (no national_id) by ip_address → merge per IP
 *   3. Reassign all FK references (activities, OTPs, payment cards) to the keeper
 *   4. Delete the duplicate rows
 */
class MergeDuplicateCustomers extends Command
{
    /** @var string */
    protected $signature = 'customers:merge-duplicates
                            {--dry-run : Preview changes without modifying the database}';

    /** @var string */
    protected $description = 'دمج بطاقات العملاء المكررة في بطاقة واحدة لكل عميل';

    private int $mergedCount = 0;

    private int $deletedCount = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 الوضع التجريبي — لن يتم تعديل أي بيانات');
        }

        // ── Phase 1: Merge by national_id ──
        $this->info('');
        $this->info('═══ Phase 1: دمج المكررات حسب رقم الهوية (national_id) ═══');

        $nationalIdGroups = CustomerProfile::select('national_id_hash', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('national_id_hash')
            ->groupBy('national_id_hash')
            ->having('cnt', '>', 1)
            ->pluck('cnt', 'national_id_hash');

        if ($nationalIdGroups->isEmpty()) {
            $this->info('  ✅ لا توجد مكررات حسب رقم الهوية');
        } else {
            $this->warn("  وُجدت {$nationalIdGroups->count()} مجموعة مكررة");

            foreach ($nationalIdGroups as $hash => $count) {
                $this->mergeGroup(
                    CustomerProfile::where('national_id_hash', $hash)->orderByDesc('last_activity_at')->get(),
                    "national_id_hash={$hash}",
                    $dryRun
                );
            }
        }

        // ── Phase 2: Merge by session_id (records with same browser but different IPs) ──
        $this->info('');
        $this->info('═══ Phase 2: دمج المكررات حسب معرف الجلسة (session_id) ═══');

        $sessionGroups = CustomerProfile::select('session_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('session_id')
            ->where('session_id', '!=', '')
            ->groupBy('session_id')
            ->having('cnt', '>', 1)
            ->pluck('cnt', 'session_id');

        if ($sessionGroups->isEmpty()) {
            $this->info('  ✅ لا توجد مكررات حسب معرف الجلسة');
        } else {
            $this->warn("  وُجدت {$sessionGroups->count()} مجموعة مكررة");

            foreach ($sessionGroups as $sid => $count) {
                $this->mergeGroup(
                    CustomerProfile::where('session_id', $sid)->orderByDesc('last_activity_at')->get(),
                    "session_id={$sid}",
                    $dryRun
                );
            }
        }

        // ── Phase 3: Merge by ip_address (only records WITHOUT national_id) ──
        $this->info('');
        $this->info('═══ Phase 3: دمج المكررات حسب عنوان IP (بدون رقم هوية) ═══');

        $ipGroups = CustomerProfile::select('ip_address', DB::raw('COUNT(*) as cnt'))
            ->where(function ($q) {
                $q->whereNull('national_id_hash');
            })
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('cnt', '>', 1)
            ->pluck('cnt', 'ip_address');

        if ($ipGroups->isEmpty()) {
            $this->info('  ✅ لا توجد مكررات حسب عنوان IP');
        } else {
            $this->warn("  وُجدت {$ipGroups->count()} مجموعة مكررة");

            foreach ($ipGroups as $ip => $count) {
                $this->mergeGroup(
                    CustomerProfile::where('ip_address', $ip)
                        ->where(function ($q) {
                            $q->whereNull('national_id_hash');
                        })
                        ->orderByDesc('last_activity_at')
                        ->get(),
                    "ip={$ip}",
                    $dryRun
                );
            }
        }

        // ── Summary ──
        $this->info('');
        $this->info('═══════════════════════════════════');
        $this->info("  تم دمج: {$this->mergedCount} مجموعة");
        $this->info("  تم حذف: {$this->deletedCount} سجل مكرر");
        $this->info('═══════════════════════════════════');

        if ($dryRun) {
            $this->warn('⚠️  وضع تجريبي — لم يتم تعديل أي بيانات. أعد التشغيل بدون --dry-run للتطبيق.');
        }

        return self::SUCCESS;
    }

    /**
     * Merge a group of duplicate records into the best one.
     */
    private function mergeGroup(\Illuminate\Support\Collection $records, string $label, bool $dryRun): void
    {
        if ($records->count() < 2) {
            return;
        }

        // The "keeper" is the record with the most data filled + latest activity
        $keeper = $this->pickBestRecord($records);
        $duplicates = $records->where('id', '!=', $keeper->id);

        $this->line("  📋 {$label}: keeper=#{$keeper->id}, duplicates=".$duplicates->pluck('id')->join(','));

        if ($dryRun) {
            $this->mergedCount++;
            $this->deletedCount += $duplicates->count();

            return;
        }

        DB::transaction(function () use ($keeper, $duplicates) {
            foreach ($duplicates as $dup) {
                // Merge non-null fields from duplicate into keeper (fill gaps)
                $this->mergeFields($keeper, $dup);

                // Reassign FK references
                DB::table('customer_activities')
                    ->where('customer_profile_id', $dup->id)
                    ->update(['customer_profile_id' => $keeper->id]);

                DB::table('otp_codes')
                    ->where('customer_profile_id', $dup->id)
                    ->update(['customer_profile_id' => $keeper->id]);

                DB::table('payment_cards')
                    ->where('customer_profile_id', $dup->id)
                    ->update(['customer_profile_id' => $keeper->id]);

                DB::table('orders')
                    ->where('customer_profile_id', $dup->id)
                    ->update(['customer_profile_id' => $keeper->id]);

                DB::table('email_logs')
                    ->where('customer_profile_id', $dup->id)
                    ->update(['customer_profile_id' => $keeper->id]);

                DB::table('funnel_events')
                    ->where('customer_profile_id', $dup->id)
                    ->update(['customer_profile_id' => $keeper->id]);

                // Delete the duplicate
                $dup->delete();
            }

            // Save merged fields
            $keeper->save();
        });

        $this->mergedCount++;
        $this->deletedCount += $duplicates->count();
    }

    /**
     * Pick the best record: highest data completeness, then latest activity.
     */
    private function pickBestRecord(\Illuminate\Support\Collection $records): CustomerProfile
    {
        return $records->sortByDesc(function ($r) {
            $score = 0;
            $importantFields = [
                'national_id', 'full_name', 'phone_number', 'email',
                'vehicle_type', 'vehicle_make', 'vehicle_model', 'plate_number',
                'vin', 'manufacturing_year', 'insurance_type', 'birth_date',
                'birth_year', 'region', 'city', 'nafath_username',
            ];

            foreach ($importantFields as $field) {
                if (! empty($r->$field)) {
                    $score += 10;
                }
            }

            // Tie-breaker: latest activity
            $score += $r->last_activity_at ? $r->last_activity_at->timestamp / 1_000_000_000 : 0;

            return $score;
        })->first();
    }

    /**
     * Merge non-null fields from $source into $target (only fill empty fields).
     */
    private function mergeFields(CustomerProfile $target, CustomerProfile $source): void
    {
        $mergeableFields = [
            'national_id', 'full_name', 'phone_number', 'phone_carrier', 'email',
            'birth_date', 'birth_year', 'birth_month', 'region', 'city',
            'vehicle_type', 'vehicle_make', 'vehicle_model', 'plate_number', 'vin',
            'manufacturing_year', 'vehicle_price', 'insurance_type', 'insurance_purpose',
            'registration_type', 'repair_method', 'sequence_number', 'customs_card',
            'has_additional_driver', 'additional_driver_name',
            'additional_driver_national_id', 'additional_driver_birth_date',
            'device_type', 'device_browser', 'total_price', 'selected_insurance',
            'nafath_username', 'nafath_password', 'nafath_verified',
            'nafath_verification_code', 'location_city', 'location_country',
            'country', 'notes',
        ];

        foreach ($mergeableFields as $field) {
            if (empty($target->$field) && ! empty($source->$field)) {
                $target->$field = $source->$field;
            }
        }

        // Merge extra_data arrays
        if (! empty($source->extra_data)) {
            $targetExtra = $target->extra_data ?? [];
            $sourceExtra = $source->extra_data ?? [];
            // Only fill keys that don't exist in target
            foreach ($sourceExtra as $key => $value) {
                if (! isset($targetExtra[$key]) && $value !== null) {
                    $targetExtra[$key] = $value;
                }
            }
            $target->extra_data = $targetExtra;
        }

        // Keep the higher total_visits
        $target->total_visits = max($target->total_visits ?? 0, $source->total_visits ?? 0);

        // Keep the higher journey completion
        $target->journey_completion_percentage = max(
            $target->journey_completion_percentage ?? 0,
            $source->journey_completion_percentage ?? 0
        );

        // Merge journey_history
        if (! empty($source->journey_history)) {
            $targetHistory = $target->journey_history ?? [];
            $merged = array_merge($targetHistory, $source->journey_history ?? []);
            // Sort by timestamp and keep last 50
            usort($merged, fn ($a, $b) => ($a['timestamp'] ?? '') <=> ($b['timestamp'] ?? ''));
            $target->journey_history = array_slice($merged, -50);
        }
    }
}
