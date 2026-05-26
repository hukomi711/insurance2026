<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/api/admin/login';

    /**
     * Admin can log in with valid credentials and receive a token.
     */
    public function test_admin_can_login_with_valid_credentials(): void
    {
        config(['services.admin.verification_email' => 'admin@test.com']);

        $user = User::factory()->create([
            'role'     => 'admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['success', 'requires_2fa', 'pending_token', 'message']);
    }

    /**
     * Login fails with wrong password.
     */
    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'role'     => 'admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Login fails when user is not an admin.
     */
    public function test_non_admin_user_cannot_login(): void
    {
        User::factory()->create([
            'role'     => 'customer',
            'email'    => 'user@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'user@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Login requires email and password fields.
     */
    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson($this->loginUrl, []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Brute-force protection returns 429 after 5 failed attempts.
     */
    public function test_brute_force_lockout_after_five_attempts(): void
    {
        User::factory()->create([
            'role'     => 'admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('secret123'),
        ]);

        // Simulate 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson($this->loginUrl, [
                'email'    => 'admin@test.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be rate-limited
        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Login returns 503 when verification email transport fails.
     */
    public function test_login_returns_503_when_verification_mail_fails(): void
    {
        config(['services.admin.verification_email' => 'admin@test.com']);

        User::factory()->create([
            'role'     => 'admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('secret123'),
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \RuntimeException('SMTP down'));

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
            ]);
    }
}
