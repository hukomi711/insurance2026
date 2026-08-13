<?php

namespace Tests\Feature;

use App\Support\CorsAllowedOrigins;
use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    private const FIRST_ORIGIN = 'https://a.example';

    private const SECOND_ORIGIN = 'https://b.example';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cors.allowed_origins' => CorsAllowedOrigins::normalize(
                self::FIRST_ORIGIN.', '.self::SECOND_ORIGIN,
            ),
            'cors.supports_credentials' => true,
        ]);
    }

    public function test_preflight_allows_socket_and_csrf_headers(): void
    {
        $response = $this->call('OPTIONS', '/api/health', server: [
            'HTTP_ORIGIN' => self::FIRST_ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-Socket-ID, X-CSRF-TOKEN',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', self::FIRST_ORIGIN);

        $allowedHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('x-socket-id', $allowedHeaders);
        $this->assertStringContainsString('x-csrf-token', $allowedHeaders);
    }

    public function test_trimmed_origin_after_comma_is_allowed(): void
    {
        $response = $this->withHeader('Origin', self::SECOND_ORIGIN)
            ->getJson('/api/health');

        $response->assertOk();
        $response->assertHeader('Access-Control-Allow-Origin', self::SECOND_ORIGIN);
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_unlisted_origin_is_not_accepted(): void
    {
        $response = $this->withHeader('Origin', 'https://unlisted.example')
            ->getJson('/api/health');

        $response->assertOk();
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_same_origin_request_without_origin_header_is_unchanged(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_credentials_are_never_combined_with_global_wildcard(): void
    {
        $this->assertTrue(config('cors.supports_credentials'));
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }
}
