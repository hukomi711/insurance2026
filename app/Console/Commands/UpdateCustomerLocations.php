<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use App\Services\GeoLocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateCustomerLocations extends Command
{
    /** @var string */
    protected $signature = 'customers:update-locations
                            {--force : تحديث جميع العملاء حتى لو كان لديهم موقع}
                            {--dry-run : عرض التغييرات بدون تطبيقها}
                            {--limit=0 : أقصى عدد للعملاء (0 = بلا حدود)}';

    /** @var string */
    protected $description = 'تحديث بيانات الموقع الجغرافي للعملاء الذين ليس لديهم موقع محدد';

    /**
     * Free ip-api.com allows 45 requests/minute.
     * We send up to BATCH_SIZE requests then sleep for BATCH_PAUSE_SECONDS.
     */
    private const BATCH_SIZE = 40;

    private const BATCH_PAUSE_SECONDS = 62;

    public function handle(GeoLocationService $geoService): int
    {
        $this->info('🌍 جاري تحديث بيانات الموقع الجغرافي...');

        $query = CustomerProfile::query();

        if (! $this->option('force')) {
            $query->whereNull('country');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        // Group by unique IP to avoid duplicate API calls
        $customers = $query->orderByDesc('last_activity_at')->get();

        if ($customers->isEmpty()) {
            $this->info('✅ جميع العملاء لديهم بيانات موقع محددة.');

            return self::SUCCESS;
        }

        // Deduplicate: group customers by IP, look up once per IP
        $grouped = $customers->groupBy('ip_address');
        $uniqueIps = $grouped->keys();

        $this->info("📊 عدد العملاء: {$customers->count()} | عناوين IP فريدة: {$uniqueIps->count()}");

        if ($this->option('dry-run')) {
            $publicIps = $uniqueIps->reject(fn ($ip) => $this->isNonPublicIp((string) $ip));
            $this->info("[DRY RUN] Would look up {$publicIps->count()} public IP address(es); no API calls or cache writes were made.");

            return self::SUCCESS;
        }

        $updated = 0;
        $failed = 0;
        $skipped = 0;
        $apiCalls = 0;

        $bar = $this->output->createProgressBar($uniqueIps->count());
        $bar->start();

        foreach ($uniqueIps as $ip) {
            $profiles = $grouped[$ip];

            // Skip invalid, private, loopback, link-local, and reserved addresses.
            if ($this->isNonPublicIp((string) $ip)) {
                $skipped += $profiles->count();
                $bar->advance();

                continue;
            }

            // Check if this IP is already cached (24h cache in GeoLocationService)
            $isCached = Cache::has("geo_location_{$ip}");

            $location = $geoService->getLocation($ip);

            if (! $isCached) {
                $apiCalls++;

                // Rate-limit: pause after BATCH_SIZE uncached lookups
                if ($apiCalls % self::BATCH_SIZE === 0) {
                    $bar->clear();
                    $this->newLine();
                    $this->warn("⏳ تم إرسال {$apiCalls} طلب — انتظار ".self::BATCH_PAUSE_SECONDS.'s لتجنب حد الطلبات...');
                    sleep(self::BATCH_PAUSE_SECONDS);
                    $bar->display();
                }
            }

            if ($location) {
                $city = $geoService->getArabicCityName($location['city'] ?? '');

                foreach ($profiles as $profile) {
                    $profile->location_city = $city;
                    $profile->location_country = $geoService->getArabicCountryName($location['country_code'] ?? '') ?: ($location['country'] ?? null);
                    $profile->country = $location['country_code'] ?? null; // ISO 2-letter code
                    // لا نكتب على region/city — هذه مخصصة لبيانات النموذج
                    $profile->save();
                }
                $updated += $profiles->count();
            } else {
                $failed += $profiles->count();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ تم التحديث: {$updated}");
        $this->info("⏭️ تم التخطي: {$skipped}");
        $this->info("🌐 طلبات API: {$apiCalls} (الباقي من الكاش)");

        if ($failed > 0) {
            $this->warn("❌ فشل: {$failed}");
        }

        return self::SUCCESS;
    }

    private function isNonPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }
}
