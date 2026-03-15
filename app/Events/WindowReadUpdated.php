<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Lightweight instant broadcast: notifies ALL admin dashboards that
 * a data-section button (vehicle / insurance / payment) has been read.
 *
 * Uses ShouldBroadcastNow (bypasses queue) so every admin sees
 * the blink disappear within milliseconds — not seconds.
 */
class WindowReadUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public string $ip;
    public string $section;
    public string $lastReadAt;
    public int $readBy;

    public function __construct(
        string $ip,
        string $section,
        string $lastReadAt,
        int $readBy
    ) {
        $this->ip         = $ip;
        $this->section    = $section;
        $this->lastReadAt = $lastReadAt;
        $this->readBy     = $readBy;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'window.read.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'ip'           => $this->ip,
            'section'      => $this->section,
            'last_read_at' => $this->lastReadAt,
            'read_by'      => $this->readBy,
        ];
    }
}
