<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaMiddlewareTest extends TestCase
{
    public function test_contact_bypasses_recaptcha_when_disabled(): void
    {
        config()->set('services.recaptcha.enabled', false);

        $response = $this->postJson('/api/contact', [
            'name' => 'Test User',
            // Intentionally omit email/message to prove request reached controller validation.
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'message'])
            ->assertJsonMissingPath('code');
    }

    public function test_contact_rejects_missing_recaptcha_token_when_enabled(): void
    {
        config()->set('services.recaptcha.enabled', true);
        config()->set('services.recaptcha.secret_key', 'test-secret');

        $response = $this->postJson('/api/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'recaptcha_token_missing');
    }

    public function test_contact_rejects_invalid_recaptcha_token_when_enabled(): void
    {
        config()->set('services.recaptcha.enabled', true);
        config()->set('services.recaptcha.secret_key', 'test-secret');

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => false,
            ], 200),
        ]);

        $response = $this->postJson('/api/contact', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello',
            'recaptcha_token' => 'invalid-token',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'recaptcha_verification_failed');
    }

    public function test_contact_reaches_validation_when_recaptcha_token_is_valid(): void
    {
        config()->set('services.recaptcha.enabled', true);
        config()->set('services.recaptcha.secret_key', 'test-secret');

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $response = $this->postJson('/api/contact', [
            'name' => 'Test User',
            // Intentionally omit message to avoid DB dependency and assert pass-through.
            'email' => 'test@example.com',
            'recaptcha_token' => 'valid-token',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('message')
            ->assertJsonMissingPath('code');
    }
}
