<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerActivityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Queue name — handled by Horizon's broadcasts-supervisor.
     */
    public string $broadcastQueue = 'broadcasts';

    /**
     * Lightweight payload — no Eloquent model serialization.
     * This prevents the full CustomerProfile (with relations) from being
     * serialized into the queue, keeping memory usage minimal.
     */
    public int $customerId;
    public string $ipAddress;
    public ?string $currentPage;
    public bool $isActive;
    public string $activityType;

    public function __construct(
        int $customerId,
        string $ipAddress,
        ?string $currentPage,
        bool $isActive,
        string $activityType = 'page_view'
    ) {
        $this->customerId   = $customerId;
        $this->ipAddress    = $ipAddress;
        $this->currentPage  = $currentPage;
        $this->isActive     = $isActive;
        $this->activityType = $activityType;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'customer.activity.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'customer_id'   => $this->customerId,
            'ip_address'    => $this->ipAddress,
            'current_page'  => $this->currentPage,
            'is_active'     => $this->isActive,
            'activity_type' => $this->activityType,
            'timestamp'     => now()->toISOString(),
        ];
    }
}
