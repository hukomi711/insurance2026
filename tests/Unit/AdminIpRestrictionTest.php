<?php

namespace Tests\Unit;

use App\Http\Middleware\AdminIpRestriction;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests for AdminIpRestriction middleware — Fail-Closed IP whitelist.
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

    public function test_blocked_ip_returns_403_for_api(): void
    {
        $geo = $this->createMock(GeoLocationService::class);
        $geo->method('isAdminIp')->willReturn(false);

        $middleware = new AdminIpRestriction($geo);
        $request = Request::create('/api/admin/something');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
    }

    public function test_blocked_ip_returns_404_for_web(): void
    {
        $geo = $this->createMock(GeoLocationService::class);
        $geo->method('isAdminIp')->willReturn(false);

        $middleware = new AdminIpRestriction($geo);
        $request = Request::create('/login');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }
}
