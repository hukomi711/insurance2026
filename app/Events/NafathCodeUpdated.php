<?php

namespace App\Events;

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

    public string $customerIp;
    public string $verificationCode;

    public function __construct(string $customerIp, string $verificationCode)
    {
        $this->customerIp = $customerIp;
        $this->verificationCode = $verificationCode;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('nafath.' . $this->customerIp);
    }

    public function broadcastAs(): string
    {
        return 'NafathCodeUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'customer_ip'       => $this->customerIp,
            'verification_code' => $this->verificationCode,
            'status'            => 'code_updated',
        ];
    }
}
