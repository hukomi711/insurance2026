<?php

namespace Tests\Unit;

use App\Services\DeviceDetectionService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests for DeviceDetectionService — device type and browser detection.
 */
class DeviceDetectionServiceTest extends TestCase
{
    // ── detectType ───────────────────────────────────────────────

    public function test_mobile_user_agent(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Mobile Safari/537.36');
        $this->assertEquals('mobile', DeviceDetectionService::detectType($request));
    }

    public function test_tablet_ipad_user_agent(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (iPad; CPU OS 16_0) AppleWebKit/605.1.15');
        $this->assertEquals('tablet', DeviceDetectionService::detectType($request));
    }

    public function test_desktop_user_agent(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $this->assertEquals('desktop', DeviceDetectionService::detectType($request));
    }

    public function test_null_user_agent_defaults_to_desktop(): void
    {
        $request = $this->makeRequest(null);
        $this->assertEquals('desktop', DeviceDetectionService::detectType($request));
    }

    // ── detectBrowser ───────────────────────────────────────────

    public function test_chrome_browser(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (Windows) AppleWebKit/537.36 Chrome/120.0');
        $this->assertEquals('Chrome', DeviceDetectionService::detectBrowser($request));
    }

    public function test_safari_browser(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (Macintosh) AppleWebKit/605.1.15 Safari/605.1.15');
        $this->assertEquals('Safari', DeviceDetectionService::detectBrowser($request));
    }

    public function test_firefox_browser(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (Windows) Gecko/20100101 Firefox/120.0');
        $this->assertEquals('Firefox', DeviceDetectionService::detectBrowser($request));
    }

    public function test_edge_browser(): void
    {
        $request = $this->makeRequest('Mozilla/5.0 (Windows) AppleWebKit/537.36 Chrome/120 Edg/120.0');
        $this->assertEquals('Edge', DeviceDetectionService::detectBrowser($request));
    }

    public function test_unknown_browser(): void
    {
        $request = $this->makeRequest('SomeBot/1.0');
        $this->assertEquals('Other', DeviceDetectionService::detectBrowser($request));
    }

    public function test_null_user_agent_returns_other(): void
    {
        $request = $this->makeRequest(null);
        $this->assertEquals('Other', DeviceDetectionService::detectBrowser($request));
    }

    // ── Helper ──────────────────────────────────────────────────

    private function makeRequest(?string $ua): Request
    {
        $request = Request::create('/test');
        if ($ua !== null) {
            $request->headers->set('User-Agent', $ua);
        } else {
            $request->headers->remove('User-Agent');
        }
        return $request;
    }
}
