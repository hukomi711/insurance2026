<?php

namespace Tests\Unit;

use Tests\TestCase;

class QueueTimeoutConfigurationTest extends TestCase
{
    public function test_redis_retry_after_exceeds_all_horizon_timeouts_with_safety_margin(): void
    {
        $timeouts = [];

        foreach ((array) config('horizon.defaults', []) as $supervisor) {
            $timeouts[] = (int) ($supervisor['timeout'] ?? 0);
        }

        foreach ((array) config('horizon.environments', []) as $supervisors) {
            foreach ($supervisors as $supervisor) {
                $timeouts[] = (int) ($supervisor['timeout'] ?? 0);
            }
        }

        $largestTimeout = max($timeouts);
        $retryAfter = (int) config('queue.connections.redis.retry_after');

        $this->assertGreaterThanOrEqual(
            $largestTimeout + 60,
            $retryAfter,
            "Redis retry_after ({$retryAfter}s) must exceed the largest Horizon timeout ({$largestTimeout}s) by at least 60 seconds."
        );
    }
}
