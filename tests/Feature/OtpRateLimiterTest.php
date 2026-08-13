<?php

namespace Tests\Feature;

use App\Http\Middleware\ApiGeoRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OtpRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        $this->withoutMiddleware(ApiGeoRestriction::class);
    }

    public function test_rotating_session_id_cannot_bypass_submit_ip_limit(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.77']);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $client->postJson('/api/otp/submit', [
                'otp' => (string) (1000 + $attempt),
                'session_id' => "rotated-submit-{$attempt}",
            ])->assertOk();
        }

        $client->postJson('/api/otp/submit', [
            'otp' => '1011',
            'session_id' => 'rotated-submit-11',
        ])->assertStatus(429);
    }

    public function test_rotating_session_id_cannot_bypass_resend_ip_limit(): void
    {
        $client = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.78']);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $client->postJson('/api/otp/resend', [
                'session_id' => "rotated-resend-{$attempt}",
            ])->assertOk();
        }

        $client->postJson('/api/otp/resend', [
            'session_id' => 'rotated-resend-7',
        ])->assertStatus(429);
    }
}
