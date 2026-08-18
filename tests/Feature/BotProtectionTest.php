<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspicious_admin_path_is_blocked(): void
    {
        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->postJson('/api/customer/page', ['current_page' => '/wp-admin.php']);

        $response->assertStatus(403);
        $this->assertSame(false, $response->json('success'));
    }

    public function test_bot_like_user_agent_is_blocked(): void
    {
        $response = $this->withHeader('User-Agent', 'curl/8.0')
            ->postJson('/api/customer/page', ['current_page' => '/insurance']);

        $response->assertStatus(403);
        $this->assertSame(false, $response->json('success'));
    }

    public function test_real_browser_request_is_allowed(): void
    {
        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->withHeader('X-Session-Token', 'valid-session-token-abc123')
            ->postJson('/api/customer/page', ['current_page' => '/insurance']);

        $response->assertOk();
        $this->assertSame(true, $response->json('success'));
    }
}
