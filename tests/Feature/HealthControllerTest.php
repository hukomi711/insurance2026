<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_queue_does_not_require_horizon(): void
    {
        config()->set('queue.default', 'sync');

        $this->getJson('/api/health/queues')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('checks.queue_driver', 'ok')
            ->assertJsonPath('checks.horizon', 'not_configured')
            ->assertJsonPath('checks.failed_jobs_table', 'ok');
    }
}
