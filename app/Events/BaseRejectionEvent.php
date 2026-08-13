<?php

namespace App\Events;

use App\Support\CustomerBroadcastChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
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
 *   1. Opaque customer session channel
 *   2. Private admin aggregation channel
 */
abstract class BaseRejectionEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public ?string $reason;

    public ?string $sessionId;

    public ?int $customerId;

    /** Channel prefix (e.g. 'otp', 'payment'). */
    abstract protected function channelPrefix(): string;

    /** Event name for broadcastAs(). */
    abstract protected function eventName(): string;

    public function __construct(?string $sessionId, ?string $reason = null, ?int $customerId = null)
    {
        $this->reason = $reason;
        $this->sessionId = $sessionId;
        $this->customerId = $customerId;
    }

    /**
     * @return array<Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $prefix = $this->channelPrefix();
        $channels = [];

        if ($this->sessionId !== null && $this->sessionId !== '') {
            $channels[] = new Channel(CustomerBroadcastChannel::forSession($prefix, $this->sessionId));
        }

        // 2. Private admin aggregation channel
        $channels[] = new PrivateChannel('admin.'.$prefix);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return $this->eventName();
    }

    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->customerId,
            'reason' => $this->reason,
            'status' => 'rejected',
        ];
    }
}
