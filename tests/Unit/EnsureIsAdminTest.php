<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureIsAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests for EnsureIsAdmin middleware — admin role guard.
 */
class EnsureIsAdminTest extends TestCase
{
    private EnsureIsAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureIsAdmin();
    }

    public function test_admin_user_passes(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(true);

        $request = Request::create('/admin/dashboard');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_non_admin_user_gets_403(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(false);

        $request = Request::create('/admin/dashboard');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_unauthenticated_guest_gets_403(): void
    {
        $request = Request::create('/admin/dashboard');
        $request->setUserResolver(fn () => null);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_403_returns_arabic_message(): void
    {
        $request = Request::create('/admin/dashboard');
        $request->setUserResolver(fn () => null);

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertStringContainsString('صلاحية', $json['message']);
    }
}
