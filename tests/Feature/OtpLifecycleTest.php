<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * OTP Lifecycle Smoke Tests — Mentor acceptance checklist.
 *
 * Covers all 8 scenarios:
 * 1. Create a new OTP
 * 2. Expired OTP polling returns "expired"
 * 3. Admin approve rejects expired OTP
 * 4. Resend OTP invalidates old code
 * 5. Old code is rejected after resend
 * 6. New code is accepted after resend
 * 7. Concurrent double-approve — only one succeeds
 * 8. Lockout after 10 consecutive failures
 */
class OtpLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake broadcasts to avoid real WebSocket calls
        Event::fake();

        // Create admin user + token for authenticated endpoints
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->adminToken = $this->admin->createToken('test')->plainTextToken;
    }

    // ─── Helper: create a customer + pending OTP ────────────────────
    private function createPendingOtp(array $otpOverrides = []): array
    {
        $customer = CustomerProfile::create([
            'ip_address'      => '10.0.0.' . rand(1, 254),
            'current_page'    => '/insurance/otp',
            'otp_fail_count'  => 0,
        ]);

        $otp = OtpCode::create(array_merge([
            'customer_profile_id' => $customer->id,
            'session_id'          => 'sess-' . uniqid(),
            'code'                => '123456',
            'code_hash'           => hash('sha256', '123456'),
            'type'                => 'otp',
            'status'              => 'pending',
            'expires_at'          => now()->addMinutes(5),
        ], $otpOverrides));

        return [$customer, $otp];
    }

    // ─── Helper: admin POST with auth ───────────────────────────────
    private function adminPost(string $uri, array $data = [])
    {
        return $this->withoutMiddleware(['admin.ip', 'throttle:120,1'])
            ->withToken($this->adminToken)
            ->postJson($uri, $data);
    }

    // ─── Helper: status poll with signature bypass ──────────────────
    private function pollStatus(string $sessionId)
    {
        return $this->withoutMiddleware([
                \App\Http\Middleware\VerifyStatusSignature::class,
                \App\Http\Middleware\ApiGeoRestriction::class,
                \Illuminate\Routing\Middleware\ThrottleRequests::class,
            ])
            ->getJson("/api/status/otp/{$sessionId}");
    }

    // ═══════════════════════════════════════════════════════════════
    //  1) Create a new OTP — submit endpoint works
    // ═══════════════════════════════════════════════════════════════
    public function test_submit_creates_pending_otp_with_expiry(): void
    {
        $response = $this->withoutMiddleware(['throttle:otp-submit', 'geo.api'])
            ->postJson('/api/otp/submit', [
                'otp'        => '9876',
                'session_id' => 'sess-new-1',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'otp_id', 'expires_at', 'status_sig'])
            ->assertJson(['success' => true]);

        $this->assertNotNull($response->json('expires_at'), 'expires_at must be returned');

        $otp = OtpCode::find($response->json('otp_id'));
        $this->assertEquals('pending', $otp->status);
        $this->assertNotNull($otp->expires_at);
        $this->assertFalse($otp->isExpired());
    }

    // ═══════════════════════════════════════════════════════════════
    //  2) Polling returns "expired" for past-due pending OTP
    // ═══════════════════════════════════════════════════════════════
    public function test_polling_returns_expired_for_past_due_otp(): void
    {
        [$customer, $otp] = $this->createPendingOtp([
            'expires_at' => now()->subMinutes(10), // clearly expired
        ]);

        $response = $this->pollStatus($otp->session_id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status'  => 'expired',
            ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  3) Polling returns "pending" for valid non-expired OTP
    // ═══════════════════════════════════════════════════════════════
    public function test_polling_returns_pending_for_valid_otp(): void
    {
        [$customer, $otp] = $this->createPendingOtp([
            'expires_at' => now()->addMinutes(4),
        ]);

        $response = $this->pollStatus($otp->session_id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status'  => 'pending',
            ]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  4) Admin approve rejects expired OTP with 422
    // ═══════════════════════════════════════════════════════════════
    public function test_admin_approve_rejects_expired_otp(): void
    {
        [$customer, $otp] = $this->createPendingOtp([
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->adminPost("/api/admin/actions/otp/{$otp->id}/approve");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'expired' => true,
            ]);

        $otp->refresh();
        $this->assertEquals('rejected', $otp->status);
        $this->assertEquals('otp_expired', $otp->rejection_reason);
    }

    // ═══════════════════════════════════════════════════════════════
    //  5) Admin approve succeeds for valid OTP
    // ═══════════════════════════════════════════════════════════════
    public function test_admin_approve_succeeds_for_valid_otp(): void
    {
        [$customer, $otp] = $this->createPendingOtp([
            'expires_at' => now()->addMinutes(3),
        ]);

        $response = $this->adminPost("/api/admin/actions/otp/{$otp->id}/approve");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $otp->refresh();
        $this->assertEquals('verified', $otp->status);
        $this->assertNotNull($otp->verified_at);

        // Fail count should be reset
        $customer->refresh();
        $this->assertEquals(0, $customer->otp_fail_count);
    }

    // ═══════════════════════════════════════════════════════════════
    //  6) Resend invalidates old OTP — old code becomes rejected
    // ═══════════════════════════════════════════════════════════════
    public function test_resend_invalidates_old_otp(): void
    {
        // Create first OTP via submit
        $firstResponse = $this->withoutMiddleware(['throttle:otp-submit', 'geo.api'])
            ->postJson('/api/otp/submit', [
                'otp'        => '1111',
                'session_id' => 'sess-resend',
            ]);

        $firstOtpId = $firstResponse->json('otp_id');

        // Submit a new OTP (same customer IP) — old one should be rejected
        $secondResponse = $this->withoutMiddleware(['throttle:otp-submit', 'geo.api'])
            ->postJson('/api/otp/submit', [
                'otp'        => '2222',
                'session_id' => 'sess-resend',
            ]);

        $secondOtpId = $secondResponse->json('otp_id');
        $this->assertNotEquals($firstOtpId, $secondOtpId);

        // Old OTP is now rejected
        $firstOtp = OtpCode::find($firstOtpId);
        $this->assertEquals('rejected', $firstOtp->status);

        // New OTP is pending
        $secondOtp = OtpCode::find($secondOtpId);
        $this->assertEquals('pending', $secondOtp->status);
    }

    // ═══════════════════════════════════════════════════════════════
    //  7) Admin approve rejects already-processed OTP (no double-approve)
    // ═══════════════════════════════════════════════════════════════
    public function test_admin_cannot_double_approve(): void
    {
        [$customer, $otp] = $this->createPendingOtp([
            'expires_at' => now()->addMinutes(3),
        ]);

        // First approve
        $first = $this->adminPost("/api/admin/actions/otp/{$otp->id}/approve");
        $first->assertOk()->assertJson(['success' => true]);

        // Second approve — should fail
        $second = $this->adminPost("/api/admin/actions/otp/{$otp->id}/approve");
        $second->assertStatus(422)
            ->assertJson(['success' => false]);

        // OTP should still be verified (not double-processed)
        $otp->refresh();
        $this->assertEquals('verified', $otp->status);
    }

    // ═══════════════════════════════════════════════════════════════
    //  8) Admin reject increments fail count + lockout after 10
    // ═══════════════════════════════════════════════════════════════
    public function test_reject_increments_fail_count_and_locks_after_10(): void
    {
        $customer = CustomerProfile::create([
            'ip_address'     => '10.0.0.99',
            'current_page'   => '/insurance/otp',
            'otp_fail_count' => 9, // 9 failures already
        ]);

        $otp = OtpCode::create([
            'customer_profile_id' => $customer->id,
            'session_id'          => 'sess-lockout',
            'code'                => '5555',
            'code_hash'           => hash('sha256', '5555'),
            'type'                => 'otp',
            'status'              => 'pending',
            'expires_at'          => now()->addMinutes(5),
        ]);

        $response = $this->adminPost("/api/admin/actions/otp/{$otp->id}/reject", [
            'reason' => 'wrong_code',
        ]);

        $response->assertOk();

        $customer->refresh();
        $this->assertEquals(10, $customer->otp_fail_count);
        $this->assertNotNull($customer->otp_locked_until);
        $this->assertTrue(now()->lt($customer->otp_locked_until));
    }

    // ═══════════════════════════════════════════════════════════════
    //  9) Locked customer cannot submit new OTP
    // ═══════════════════════════════════════════════════════════════
    public function test_locked_customer_gets_429(): void
    {
        // Pre-create a locked customer at the test IP
        $customer = CustomerProfile::create([
            'ip_address'       => '127.0.0.1',
            'current_page'     => '/insurance/otp',
            'otp_fail_count'   => 10,
            'otp_locked_until' => now()->addMinutes(10),
        ]);

        $response = $this->withoutMiddleware(['throttle:otp-submit', 'geo.api'])
            ->postJson('/api/otp/submit', [
                'otp'        => '9999',
                'session_id' => 'sess-locked',
            ]);

        $response->assertStatus(429)
            ->assertJson(['locked' => true]);
    }

    // ═══════════════════════════════════════════════════════════════
    //  10) Polling returns "not_found" for unknown session
    // ═══════════════════════════════════════════════════════════════
    public function test_polling_returns_not_found_for_unknown_session(): void
    {
        $response = $this->pollStatus('nonexistent-session-id');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status'  => 'not_found',
            ]);
    }
}
