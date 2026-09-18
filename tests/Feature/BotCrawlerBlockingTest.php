<?php

namespace Tests\Feature;

use Tests\TestCase;

class BotCrawlerBlockingTest extends TestCase
{
    public function test_known_bot_user_agent_is_blocked_on_web_routes(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get('/');

        $response->assertStatus(403);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function test_regular_browser_user_agent_is_not_blocked_by_bot_filter(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/128.0 Safari/537.36',
        ])->get('/robots.txt');

        $response->assertOk();
    }
}
