<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Base class for all customer-facing approval events.
 *
 * Subclasses only need to define:
 *   - protected string $channel   (e.g. 'otp', 'payment', 'stc')
 *   - protected string $eventName (e.g. 'OtpApproved', 'PaymentApproved')
 *
 * Broadcasting targets:
 *   1. Legacy public channel:  {prefix}.{ip}       (backward compat)
 *   2. Admin private channel:  admin.{prefix}      (aggregated admin feed)
 */
abstract class BaseApprovalEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public string $customerIp;
    public ?string $redirectTo;
    public ?string $sessionId;
    public ?int $customerId;

    /** Channel prefix (e.g. 'otp', 'payment'). */
    abstract protected function channelPrefix(): string;

    /** Event name for broadcastAs(). */
    abstract protected function eventName(): string;

    public function __construct(string $customerIp, ?string $redirectTo = null, ?string $sessionId = null, ?int $customerId = null)
    {
        $this->customerIp = $customerIp;
        $this->redirectTo = $redirectTo;
        $this->sessionId  = $sessionId;
        $this->customerId = $customerId;
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
            'customer_id' => $this->customerId,
            'redirect_to' => $this->redirectTo,
            'session_id'  => $this->sessionId,
            'status'      => 'approved',
        ];
    }
}
