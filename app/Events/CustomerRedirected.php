<?php

namespace App\Events;

use App\Support\CustomerBroadcastChannel;
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

    public string $sessionId;

    public string $redirectUrl;

    public int $customerId;

    public function __construct(string $sessionId, string $redirectUrl, int $customerId)
    {
        $this->sessionId = $sessionId;
        $this->redirectUrl = $redirectUrl;
        $this->customerId = $customerId;
    }

    /**
     * The channel the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel(CustomerBroadcastChannel::forSession('customer', $this->sessionId));
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
            'customer_id' => $this->customerId,
            'redirect_url' => $this->redirectUrl,
        ];
    }
}
