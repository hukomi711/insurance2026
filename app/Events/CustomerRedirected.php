<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerRedirected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Queue name — handled by Horizon's broadcasts-supervisor.
     */
    public string $broadcastQueue = 'broadcasts';

    public string $customerIp;
    public string $redirectUrl;

    public function __construct(string $customerIp, string $redirectUrl)
    {
        $this->customerIp = $customerIp;
        $this->redirectUrl = $redirectUrl;
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
        return 'CustomerRedirected';
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'customer_ip' => $this->customerIp,
            'redirect_url' => $this->redirectUrl,
        ];
    }
}
