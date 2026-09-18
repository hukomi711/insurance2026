<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\BlockBotsAndCrawlers;
use Illuminate\Http\Request;
use Tests\TestCase;

class BlockBotsAndCrawlersTest extends TestCase
{
    protected BlockBotsAndCrawlers $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new BlockBotsAndCrawlers();
    }

    /**
     * Test that legitimate traffic is allowed
     */
    public function test_legitimate_user_agent_is_allowed()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test that blocked bots are denied
     */
    public function test_blocked_bot_is_denied()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; AhrefsBot/7.0)');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Forbidden', $response->getContent());
    }

    /**
     * Test that health check paths always bypass blocking
     */
    public function test_health_check_paths_allow_all_bots()
    {
        $routes = [
            '/api/health',
            '/api/health/queues',
            '/api/health/realtime',
            '/api/health/database',
            '/api/health/cache',
            '/.well-known/health',
            '/ping',
            '/health',
        ];

        foreach ($routes as $route) {
            $request = Request::create($route, 'GET');
            $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; AhrefsBot/7.0)');

            $response = $this->middleware->handle($request, fn($req) => response('OK'));

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('OK', $response->getContent());
        }
    }

    /**
     * Test that allowed uptime monitoring bots are allowed
     */
    public function test_allowed_uptime_monitoring_bots_are_allowed()
    {
        $allowedBots = [
            'UptimeRobot/2.0 (https://uptimerobot.com/)',
            'Uptime.com Uptime Monitoring',
            'Pingdom.com_bot_version_1.4',
            'Site24x7 Daily Uptime Monitor',
            'Statuspage.io Uptime Monitor',
        ];

        foreach ($allowedBots as $ua) {
            $request = Request::create('/', 'GET');
            $request->headers->set('User-Agent', $ua);

            $response = $this->middleware->handle($request, fn($req) => response('OK'));

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('OK', $response->getContent());
        }
    }

    /**
     * Test that allowed monitoring bots can access non-health endpoints
     */
    public function test_allowed_bots_bypass_blocking_on_all_paths()
    {
        $request = Request::create('/some/page', 'GET');
        $request->headers->set('User-Agent', 'UptimeRobot/2.0 (https://uptimerobot.com/)');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test that blocking can be disabled globally
     */
    public function test_bot_blocking_can_be_disabled()
    {
        config(['bot_handling.blocking_enabled' => false]);

        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; AhrefsBot/7.0)');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test that various blocked patterns are caught
     */
    public function test_various_blocked_patterns_are_caught()
    {
        $blockedUAs = [
            'Mozilla/5.0 (compatible; Googlebot/2.1)',
            'Mozilla/5.0 (compatible; bingbot/2.0)',
            'facebookexternalhit/1.1',
            'Twitterbot/1.0',
            'linkedinbot/1.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Headless Chrome',
            'python-requests/2.28.1',
            'curl/7.68.0',
            'Mozilla/5.0 Selenium/4.0.0',
        ];

        foreach ($blockedUAs as $ua) {
            $request = Request::create('/', 'GET');
            $request->headers->set('User-Agent', $ua);

            $response = $this->middleware->handle($request, fn($req) => response('OK'));

            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Expected to block user-agent: {$ua}"
            );
        }
    }

    /**
     * Test that response headers are set correctly for blocked requests
     */
    public function test_blocked_response_headers_are_set()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; AhrefsBot/7.0)');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('noindex', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('nofollow', $response->headers->get('X-Robots-Tag'));
    }

    /**
     * Test that empty user-agent is allowed
     */
    public function test_empty_user_agent_is_allowed()
    {
        $request = Request::create('/', 'GET');
        // Don't set User-Agent header

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test case-insensitivity of user-agent matching
     */
    public function test_user_agent_matching_is_case_insensitive()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', 'UPTIMEROBOT/2.0 (HTTPS://UPTIMEROBOT.COM/)');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that whitespace is trimmed from user-agent
     */
    public function test_whitespace_is_trimmed_from_user_agent()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('User-Agent', '   Mozilla/5.0   ');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that health check subpaths are also allowed
     */
    public function test_health_check_subpaths_are_allowed()
    {
        $request = Request::create('/api/health/some-service', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; AhrefsBot/7.0)');

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
