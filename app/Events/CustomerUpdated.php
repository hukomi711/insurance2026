<?php

namespace App\Events;

use App\Models\CustomerProfile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Queue name — handled by Horizon's broadcasts-supervisor.
     */
    public string $broadcastQueue = 'broadcasts';

    /**
     * Lightweight payload — avoid serializing the full Eloquent model.
     */
    public string $customerIp;
    public int $customerId;
    public string $action;
    public ?string $currentStep;
    public ?int $completionPercentage;
    public bool $isActive;
    public ?string $updatedAt;

    public function __construct(CustomerProfile $customer, string $action = 'updated')
    {
        // Extract scalars — do NOT serialize the Eloquent model into the queue
        $this->customerIp           = $customer->ip_address;
        $this->customerId           = $customer->id;
        $this->action               = $action;
        $this->currentStep          = $customer->current_step;
        $this->completionPercentage = $customer->journey_completion_percentage ?? $customer->completion_percentage;
        $this->isActive             = (bool) $customer->is_active;
        $this->updatedAt            = $customer->updated_at?->toISOString();
    }

    /**
     * The channel the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('customer.' . $this->customerIp);
    }

    /**
     * The event name for the client to listen on.
     */
    public function broadcastAs(): string
    {
        return 'CustomerUpdated';
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'customer_ip' => $this->customerIp,
            'customer_id' => $this->customerId,
            'action'      => $this->action,
            'step'        => $this->currentStep,
            'percentage'  => $this->completionPercentage,
            'is_active'   => $this->isActive,
            'updated_at'  => $this->updatedAt,
        ];
    }
}
