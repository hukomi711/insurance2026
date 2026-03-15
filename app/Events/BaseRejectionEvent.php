<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for all customer-facing rejection events.
 *
 * Subclasses only need to define:
 *   - protected string $channel   (e.g. 'otp', 'payment', 'stc')
 *   - protected string $eventName (e.g. 'OtpRejected', 'PaymentRejected')
 *
 * Broadcasting targets:
 *   1. Legacy public channel:  {prefix}.{ip}       (backward compat)
 *   2. Admin private channel:  admin.{prefix}      (aggregated admin feed)
 */
abstract class BaseRejectionEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public string $customerIp;
    public ?string $reason;
    public ?string $sessionId;

    /** Channel prefix (e.g. 'otp', 'payment'). */
    abstract protected function channelPrefix(): string;

    /** Event name for broadcastAs(). */
    abstract protected function eventName(): string;

    public function __construct(string $customerIp, ?string $reason = null, ?string $sessionId = null)
    {
        $this->customerIp = $customerIp;
        $this->reason = $reason;
        $this->sessionId = $sessionId;
    }

    /**
     * @return array<Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $prefix   = $this->channelPrefix();
        $channels = [];

        // 1. Legacy IP-based public channel (existing customers keep working)
        $channels[] = new Channel($prefix . '.' . $this->customerIp);

        // 2. Private admin aggregation channel
        $channels[] = new PrivateChannel('admin.' . $prefix);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return $this->eventName();
    }

    public function broadcastWith(): array
    {
        return [
            'customer_ip' => $this->customerIp,
            'reason'      => $this->reason,
            'session_id'  => $this->sessionId,
            'status'      => 'rejected',
        ];
    }
}
