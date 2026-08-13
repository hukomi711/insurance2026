<?php

namespace Tests\Unit;

use App\Events\CustomerActivityUpdated;
use App\Events\NafathApproved;
use App\Events\OtpApproved;
use App\Events\OtpRejected;
use App\Events\PaymentApproved;
use App\Support\CustomerBroadcastChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerBroadcastEventTest extends TestCase
{
    public function test_customer_channel_uses_session_hash_and_not_ip_or_raw_session(): void
    {
        $sessionId = '4b2686c3-92ea-49b4-b7fd-ea673263f27f';
        $event = new PaymentApproved($sessionId, null, 42);

        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertSame(CustomerBroadcastChannel::forSession('payment', $sessionId), $channels[0]->name);
        $this->assertStringNotContainsString($sessionId, $channels[0]->name);
        $this->assertStringNotContainsString('203.0.113.10', $channels[0]->name);
        $this->assertInstanceOf(PrivateChannel::class, $channels[1]);
        $this->assertSame('private-admin.payment', $channels[1]->name);
    }

    public function test_customer_payload_does_not_expose_ip_or_session_token(): void
    {
        $payload = (new NafathApproved('browser-session', '83', '/insurance/nafath/callback', 7))
            ->broadcastWith();

        $this->assertSame(7, $payload['customer_id']);
        $this->assertSame('83', $payload['verification_code']);
        $this->assertArrayNotHasKey('customer_ip', $payload);
        $this->assertArrayNotHasKey('session_id', $payload);
    }

    public function test_events_without_a_session_only_broadcast_to_admins(): void
    {
        $channels = (new OtpRejected(null, 'invalid', 9))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-admin.otp', $channels[0]->name);
    }

    public function test_to_others_socket_support_is_available_on_base_events(): void
    {
        $event = new OtpApproved('session-id', null, 5);

        $this->assertTrue(method_exists($event, 'dontBroadcastToCurrentUser'));
    }

    public function test_activity_timestamp_is_captured_when_event_is_created(): void
    {
        Carbon::setTestNow('2026-08-13 10:00:00');
        $event = new CustomerActivityUpdated(1, '203.0.113.10', '/checkout', true);
        $occurredAt = $event->broadcastWith()['timestamp'];

        Carbon::setTestNow('2026-08-13 10:05:00');

        $this->assertSame($occurredAt, $event->broadcastWith()['timestamp']);
        Carbon::setTestNow();
    }
}
