<?php

namespace Tests\Unit;

use App\Http\Middleware\ApiGeoRestriction;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests for ApiGeoRestriction middleware — API geo-blocking with Fail-Open.
 */
class ApiGeoRestrictionTest extends TestCase
{
    private function makeMiddleware(bool $enabled, bool $isAdmin, bool $isSaudi): ApiGeoRestriction
    {
        $geo = $this->createMock(GeoLocationService::class);
        $geo->method('isEnabled')->willReturn($enabled);
        $geo->method('isAdminIp')->willReturn($isAdmin);
        $geo->method('isSaudiArabia')->willReturn($isSaudi);

        return new ApiGeoRestriction($geo);
    }

    public function test_disabled_geo_allows_all(): void
    {
        $middleware = $this->makeMiddleware(enabled: false, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/quotes/calculate');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_exempt_path_geo_check_allowed(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/geo/check');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_exempt_path_newsletter_allowed(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/newsletter');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_exempt_path_customer_wildcard_allowed(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/customer/track');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_exempt_path_quote_tracking_allowed(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/quote/abc123');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_public_quote_calculation_exempted(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/quotes/calculate');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_admin_ip_allowed(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: true, isSaudi: false);
        $request = Request::create('/api/quotes/calculate');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_saudi_ip_allowed(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: true);
        $request = Request::create('/api/quotes/calculate');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_foreign_ip_blocked_on_protected_api(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/orders');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('السعودية', $json['message']);
    }

    public function test_authenticated_user_bypasses_geo(): void
    {
        $middleware = $this->makeMiddleware(enabled: true, isAdmin: false, isSaudi: false);
        $request = Request::create('/api/quotes/calculate');
        $request->setUserResolver(fn () => new \stdClass()); // any authenticated user

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
