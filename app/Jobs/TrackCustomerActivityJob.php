<?php

namespace App\Jobs;

use App\Events\CustomerActivityUpdated;
use App\Models\CustomerProfile;
use App\Models\UserActivity;
use App\Services\GeoLocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrackCustomerActivityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $ipAddress = '0.0.0.0',
        protected string $path = '/',
        protected ?string $sessionId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeoLocationService $geoService): void
    {
        $updateData = [
            'current_page' => mb_substr($this->path, 0, 1024),
        ];

        $geoData = $geoService->getLocation($this->ipAddress);

        if ($geoData) {
            $countryCode = $geoData['country_code'] ?? null;
            $arabicCity = $geoService->getArabicCityName($geoData['city'] ?? '');
            $arabicCountry = $geoService->getArabicCountryName($countryCode);

            $updateData['country'] = $countryCode;                    // ISO 2-letter code (SA, AE, …)
            $updateData['city'] = $arabicCity;
            $updateData['region'] = $geoData['region'] ?? null;
            $updateData['location_city'] = $arabicCity;               // Geo-detected city (Arabic)
            $updateData['location_country'] = $arabicCountry ?: null; // Geo-detected country (Arabic)
        }

        if ($this->sessionId) {
            $updateData['session_id'] = $this->sessionId;
        }

        $customer = CustomerProfile::createOrUpdateByIP($this->ipAddress, $updateData);
        $customer->increment('total_pages_visited');

        UserActivity::create([
            'ip_address' => $this->ipAddress,
            'page' => mb_substr($this->path, 0, 2048),
            'action' => 'page_view',
            'device_type' => $customer->device_type,
            'device_browser' => $customer->device_browser,
        ]);

        $this->updateJourney($customer, $this->path);

        if (config('broadcasting.default') !== 'null' && config('broadcasting.default') !== 'log') {
            try {
                event(new CustomerActivityUpdated(
                    $customer->id,
                    $customer->ip_address,
                    $customer->current_page,
                    $customer->is_active,
                    'page_view'
                ));
            } catch (\Exception $e) {
                report($e);
            }
        }
    }

    protected function updateJourney(CustomerProfile $customer, string $currentPage): void
    {
        $journey = $customer->journey_history ?? [];

        $journey[] = [
            'page' => $currentPage,
            'timestamp' => now()->toISOString(),
        ];

        if (count($journey) > 50) {
            $journey = array_slice($journey, -50);
        }

        $customer->update([
            'journey_history' => $journey,
        ]);

        $this->updateJourneyCompletion($customer, $currentPage);
    }

    protected function updateJourneyCompletion(CustomerProfile $customer, string $currentPage): void
    {
        $completedSteps = 0;
        $totalSteps = 7;

        // Map real frontend route patterns to funnel steps
        $importantPages = [
            'motorapp/basicDetails' => 1,  // Vehicle info forms
            'motorapp/vehicleDetails' => 2,
            'motorapp/policyDetailsFlow' => 3,
            'compare' => 4,
            'checkout' => 5,
            'insurance/otp' => 5,
            'insurance/card-pin' => 5,
            'insurance/payment' => 6,
            'insurance/phone' => 5,
            'insurance/stc' => 5,
            'insurance/nafath' => 5,
            'order-review' => 5,
            'confirmation' => 7,
        ];

        foreach ($importantPages as $page => $step) {
            // Check current page
            if (str_contains($currentPage, $page)) {
                $completedSteps = max($completedSteps, $step);
            }
            // Check journey history
            if ($customer->journey_history) {
                foreach ($customer->journey_history as $visit) {
                    if (str_contains($visit['page'] ?? '', $page)) {
                        $completedSteps = max($completedSteps, $step);
                        break;
                    }
                }
            }
        }

        $percentage = min(round(($completedSteps / $totalSteps) * 100), 100);

        $customer->update([
            'journey_completion_percentage' => $percentage,
            'current_step' => $completedSteps,
        ]);
    }
}
