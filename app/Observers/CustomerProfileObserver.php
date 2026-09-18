<?php

namespace App\Observers;

use App\Events\CustomerReactivatedEvent;
use App\Models\CustomerProfile;

/**
 * Observes CustomerProfile model lifecycle events and emits domain events.
 *
 * Currently: detects reactivation (is_active: false -> true) and fires event.
 */
class CustomerProfileObserver
{
    /**
     * Detect reactivation: customer was inactive (is_active=false),
     * now transitioning to is_active=true.
     *
     * This runs BEFORE the save, so original attributes are still available.
     */
    public function updating(CustomerProfile $customer): void
    {
        // Reactivation: previously inactive, now becoming active
        if ($customer->isDirty('is_active')
            && $customer->is_active === true
            && $customer->getOriginal('is_active') === false) {

            // Calculate inactivity window from original last_activity_at
            $previousLastActivityAt = $customer->getOriginal('last_activity_at');

            $inactiveDays = $previousLastActivityAt
                ? (int) $previousLastActivityAt->diffInDays(now())
                : 0;

            // Only emit if inactivity was significant (>= 1 day)
            if ($inactiveDays >= 1) {
                event(new CustomerReactivatedEvent(
                    customerId: $customer->id,
                    ipAddress: $customer->ip_address ?? '',
                    previousLastActivityAt: $previousLastActivityAt?->toIso8601String(),
                    inactiveDays: $inactiveDays,
                ));
            }
        }
    }
}
