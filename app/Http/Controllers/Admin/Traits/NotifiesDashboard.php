<?php

namespace App\Http\Controllers\Admin\Traits;

use App\Events\CustomerActivityUpdated;
use App\Events\WindowReadUpdated;
use App\Models\CustomerProfile;
use App\Services\CustomerCacheService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait NotifiesDashboard
{
    /**
     * Flush all admin customer list caches so the next API call returns fresh data.
     * Delegates to CustomerCacheService for consistent behavior across admin and customer controllers.
     */
    protected function flushCustomerCache(): void
    {
        CustomerCacheService::flush();
    }

    /**
     * Notify the admin dashboard channel about a customer state change.
     * This ensures all admin browser tabs see updates in real-time.
     */
    protected function notifyDashboard(string $customerIp, string $activityType): void
    {
        // Flush customer cache so the next poll returns updated data
        $this->flushCustomerCache();

        try {
            $customer = CustomerProfile::where('ip_address', $customerIp)->first();
            if ($customer) {
                broadcast(new CustomerActivityUpdated(
                    $customer->id,
                    $customer->ip_address,
                    $customer->current_page,
                    $customer->is_active,
                    $activityType
                ));
            }
        } catch (\Throwable $e) {
            Log::warning("Dashboard notify failed ({$activityType}): ".$e->getMessage());
        }
    }

    /**
     * Atomically refresh data_viewed.payment after an approve/reject action.
     *
     * When an admin approves/rejects an OTP/card/phone/nafath, the status change
     * alters the payment section hash. Without this update, other admins would see
     * a false re-blink because the stored hash no longer matches the new state.
     *
     * This method re-snapshots the payment section (count + hash) and broadcasts
     * WindowReadUpdated so ALL admins see the blink cleared instantly.
     */
    protected function refreshPaymentViewed(string $customerIp): void
    {
        try {
            $customer = CustomerProfile::where('ip_address', $customerIp)
                ->with(['paymentCards', 'otpCodes'])
                ->first();

            if (! $customer) {
                return;
            }

            $viewed = $customer->data_viewed ?? [];

            // Re-compute payment count
            $count = $customer->paymentCards->count()
                + $customer->otpCodes->where('type', 'otp')->count()
                + $customer->otpCodes->where('type', 'pin')->count();

            // Re-compute payment hash (must match AdminCustomerController::computeSectionHash)
            $hashValues = [
                $customer->paymentCards->sortBy('id')->map(fn ($i) => $i->id.':'.$i->status)->implode(','),
                $customer->otpCodes->whereIn('type', ['otp', 'pin'])->sortBy('id')->map(fn ($i) => $i->id.':'.$i->status)->implode(','),
                $customer->otpCodes->whereIn('type', ['phone_verification', 'stc_otp', 'stc_verification'])->sortBy('id')->map(fn ($i) => $i->id.':'.$i->status)->implode(','),
                ($customer->nafath_verified ? '1' : '0').':'.($customer->nafath_verification_code ?? ''),
            ];

            $readAt = now()->toISOString();
            $adminId = Auth::id() ?? 0;

            $viewed['payment'] = [
                'at' => $readAt,
                'by' => $adminId,
                'count' => $count,
                'hash' => md5(implode('|', array_map('strval', $hashValues))),
            ];

            $customer->update(['data_viewed' => $viewed]);

            broadcast(new WindowReadUpdated($customerIp, 'payment', $readAt, $adminId));
        } catch (\Throwable $e) {
            Log::warning('refreshPaymentViewed failed: '.$e->getMessage());
        }
    }
}
