<?php

namespace Tests\Unit;

use App\Http\Middleware\VerifyStatusSignature;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests for VerifyStatusSignature middleware — HMAC verification on status polls.
 */
class VerifyStatusSignatureTest extends TestCase
{
    private VerifyStatusSignature $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyStatusSignature();
        config(['services.status_poll.secret' => 'test-secret-key']);
    }

    public function test_valid_signature_passes(): void
    {
        $purpose = 'otp';
        $sessionId = 'abc-123';
        $sig = hash_hmac('sha256', "{$purpose}|{$sessionId}", 'test-secret-key');

        $request = Request::create("/api/status/{$purpose}/{$sessionId}?sig={$sig}");

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_missing_signature_returns_403(): void
    {
        $request = Request::create('/api/status/otp/abc-123');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Missing signature', $response->getContent());
    }

    public function test_invalid_signature_returns_403(): void
    {
        $request = Request::create('/api/status/otp/abc-123?sig=invalid-hash');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Invalid signature', $response->getContent());
    }

    public function test_cross_purpose_replay_rejected(): void
    {
        // Sign for 'otp' but use on 'pin' endpoint
        $sig = hash_hmac('sha256', 'otp|abc-123', 'test-secret-key');

        $request = Request::create("/api/status/pin/abc-123?sig={$sig}");

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_missing_secret_config_returns_403(): void
    {
        config(['services.status_poll.secret' => null]);

        $sig = hash_hmac('sha256', 'otp|abc-123', 'anything');
        $request = Request::create("/api/status/otp/abc-123?sig={$sig}");

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_missing_purpose_segment_returns_403(): void
    {
        $request = Request::create('/api/status?sig=something');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }
}
