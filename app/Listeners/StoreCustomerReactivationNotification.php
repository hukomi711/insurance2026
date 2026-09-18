<?php

namespace App\Listeners;

use App\Events\CustomerReactivatedEvent;
use App\Models\AdminEventNotification;

/**
 * Listens for CustomerReactivatedEvent and persists the notification
 * to admin_event_notifications table for admin dashboard display.
 *
 * Uses unique notification_key to prevent duplicates even if the event fires multiple times.
 */
class StoreCustomerReactivationNotification
{
    public function handle(CustomerReactivatedEvent $event): void
    {
        $key = "customer_reactivated-{$event->customerId}";

        AdminEventNotification::findOrCreateByKey(
            type: 'customer_reactivated',
            key: $key,
            referenceId: $event->customerId,
            message: "عميل عاد نشطاً بعد {$event->inactiveDays} يوم",
            metadata: [
                'customer_id' => $event->customerId,
                'customer_ip' => $event->ipAddress,
                'inactive_days' => $event->inactiveDays,
                'previous_last_activity_at' => $event->previousLastActivityAt,
                'reactivated_at' => $event->reactivatedAt,
            ]
        );
    }
}
