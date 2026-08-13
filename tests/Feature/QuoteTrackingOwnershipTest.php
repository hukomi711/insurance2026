<?php

namespace Tests\Feature;

use App\Models\QuoteSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTrackingOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'b42df6fa-68e9-4ac0-b41d-86bc40fc620e';

    public function test_browser_token_allows_active_session_to_survive_ip_changes(): void
    {
        $start = $this->withHeader('X-Session-Token', self::TOKEN)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/quote/start', ['insurance_type' => 'renew'])
            ->assertCreated();

        $uuid = $start->json('uuid');

        $this->assertDatabaseHas('quote_sessions', [
            'uuid' => $uuid,
            'browser_token_hash' => hash('sha256', self::TOKEN),
        ]);

        $this->withHeader('X-Session-Token', self::TOKEN)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->postJson("/api/quote/{$uuid}/step", [
                'step' => 'vehicle',
                'step_number' => 2,
                'exit_reason' => 'next',
            ])
            ->assertOk();
    }

    public function test_different_browser_token_cannot_read_or_mutate_session(): void
    {
        $session = QuoteSession::create([
            'customer_ip' => '203.0.113.10',
            'browser_token_hash' => hash('sha256', self::TOKEN),
            'current_step' => 'motorapp',
            'step_number' => 1,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $request = $this->withHeader(
            'X-Session-Token',
            '12e9cfcb-e630-4a46-9748-1d1dc97d8c9e',
        );

        $request->getJson("/api/quote/{$session->uuid}")->assertNotFound();
        $request->postJson("/api/quote/{$session->uuid}/step", [
            'step' => 'vehicle',
            'step_number' => 2,
        ])->assertNotFound();
    }

    public function test_abandoned_session_cannot_be_resumed_or_updated(): void
    {
        $session = QuoteSession::create([
            'customer_ip' => '203.0.113.10',
            'browser_token_hash' => hash('sha256', self::TOKEN),
            'current_step' => 'motorapp',
            'step_number' => 1,
            'status' => 'abandoned',
            'started_at' => now()->subHour(),
            'abandoned_at' => now(),
        ]);

        $request = $this->withHeader('X-Session-Token', self::TOKEN);

        $request->getJson("/api/quote/{$session->uuid}")->assertNotFound();
        $request->postJson("/api/quote/{$session->uuid}/step", [
            'step' => 'vehicle',
            'step_number' => 2,
        ])->assertNotFound();
    }

    public function test_active_legacy_session_still_uses_ip_fallback(): void
    {
        $session = QuoteSession::create([
            'customer_ip' => '127.0.0.1',
            'browser_token_hash' => null,
            'current_step' => 'motorapp',
            'step_number' => 1,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->withHeader('X-Session-Token', self::TOKEN)
            ->getJson("/api/quote/{$session->uuid}")
            ->assertOk();
    }
}
