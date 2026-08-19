<?php

namespace Tests\Unit;

use App\Http\Middleware\AdminIpRestriction;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests for AdminIpRestriction middleware — pass-through behavior.
 */
class AdminIpRestrictionTest extends TestCase
{
    public function test_allowed_ip_passes(): void
    {
        $geo = $this->createMock(GeoLocationService::class);
        $geo->method('isAdminIp')->willReturn(true);

        $middleware = new AdminIpRestriction($geo);
        $request = Request::create('/api/admin/dashboard');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocked_ip_still_passes_for_api(): void
    {
        $geo = $this->createMock(GeoLocationService::class);
        $geo->method('isAdminIp')->willReturn(false);

        $middleware = new AdminIpRestriction($geo);
        $request = Request::create('/api/admin/something');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocked_ip_still_passes_for_web(): void
    {
        $geo = $this->createMock(GeoLocationService::class);
        $geo->method('isAdminIp')->willReturn(false);

        $middleware = new AdminIpRestriction($geo);
        $request = Request::create('/login');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
