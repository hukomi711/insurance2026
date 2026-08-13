<?php

namespace App\Events;

use App\Support\CustomerBroadcastChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Standalone event  Nafath code updated (not an approval/rejection).
 * Uses ShouldBroadcastNow for instant delivery (consistent with other verification events).
 */
class NafathCodeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $sessionId;

    public string $verificationCode;

    public int $customerId;

    public function __construct(string $sessionId, string $verificationCode, int $customerId)
    {
        $this->sessionId = $sessionId;
        $this->verificationCode = $verificationCode;
        $this->customerId = $customerId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel(CustomerBroadcastChannel::forSession('nafath', $this->sessionId));
    }

    public function broadcastAs(): string
    {
        return 'NafathCodeUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->customerId,
            'verification_code' => $this->verificationCode,
            'status' => 'code_updated',
        ];
    }
}
