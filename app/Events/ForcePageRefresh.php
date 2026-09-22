<?php

namespace App\Events;

use App\Support\CustomerBroadcastChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * ForcePageRefresh
 *
 * Emitted when admin clicks "إعادة تحميل الصفحة" to perform a hard refresh
 * of the customer's current page (clearing temporary storage keys).
 *
 * This is a safety valve for cases where successive redirects may not
 * propagate correctly due to race conditions or network delays.
 */
class ForcePageRefresh implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $broadcastQueue = 'broadcasts';

    public string $sessionId;

    public int $customerId;

    /**
     * Unique command ID to prevent duplicate refresh commands.
     */
    public string $commandId;

    public function __construct(string $sessionId, int $customerId, ?string $commandId = null)
    {
        $this->sessionId = $sessionId;
        $this->customerId = $customerId;
        $this->commandId = $commandId ?? \Illuminate\Support\Str::ulid();
    }

    public function broadcastOn(): Channel
    {
        return new Channel(CustomerBroadcastChannel::forSession('customer', $this->sessionId));
    }

    public function broadcastAs(): string
    {
        return 'ForcePageRefresh';
    }

    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->customerId,
            'command_id' => $this->commandId,
        ];
    }
}
