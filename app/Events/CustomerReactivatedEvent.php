<?php

namespace App\Events;

use App\Models\CustomerProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Emitted when a customer transitions from is_active: false -> true,
 * indicating they have returned after a period of inactivity.
 *
 * Payload includes:
 *   - customerId: the customer's profile ID
 *   - ipAddress: customer's IP
 *   - previousLastActivityAt: the last_activity_at timestamp BEFORE this reactivation
 *   - inactiveDays: approximate days since previous activity
 *   - reactivatedAt: the current timestamp (when is_active was set to true)
 *
 * This event does NOT broadcast to WebSocket clients (no ShouldBroadcast).
 * Use case: admin notifications only (via Listener or Job).
 */
class CustomerReactivatedEvent
{
    use Dispatchable, InteractsWithSockets;

    public int $customerId;

    public string $ipAddress;

    public ?string $previousLastActivityAt;

    public int $inactiveDays;

    public string $reactivatedAt;

    public function __construct(
        int $customerId,
        string $ipAddress,
        ?string $previousLastActivityAt,
        int $inactiveDays
    ) {
        $this->customerId = $customerId;
        $this->ipAddress = $ipAddress;
        $this->previousLastActivityAt = $previousLastActivityAt;
        $this->inactiveDays = $inactiveDays;
        $this->reactivatedAt = now()->toIso8601String();
    }

    /**
     * Get broadcast channel (for consistency; not actually used).
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }
}
