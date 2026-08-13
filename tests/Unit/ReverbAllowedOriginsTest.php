<?php

namespace Tests\Unit;

use App\Support\ReverbAllowedOrigins;
use RuntimeException;
use Tests\TestCase;

class ReverbAllowedOriginsTest extends TestCase
{
    public function test_it_normalizes_urls_to_the_hostnames_reverb_compares(): void
    {
        $origins = ReverbAllowedOrigins::normalize(
            ' https://Example.com, http://www.example.com:8080/path,example.com ',
            false,
        );

        $this->assertSame(['example.com', 'www.example.com'], $origins);
    }

    public function test_it_preserves_supported_subdomain_patterns(): void
    {
        $this->assertSame(
            ['*.example.com'],
            ReverbAllowedOrigins::normalize('*.Example.com', true),
        );
    }

    public function test_local_empty_value_uses_restricted_local_defaults(): void
    {
        $this->assertSame(
            ['localhost', '127.0.0.1'],
            ReverbAllowedOrigins::normalize(null, false),
        );
    }

    public function test_production_rejects_an_empty_value(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be empty');

        ReverbAllowedOrigins::normalize('', true);
    }

    public function test_production_rejects_a_global_wildcard(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain "*"');

        ReverbAllowedOrigins::normalize('*', true);
    }

    public function test_reverb_config_contains_only_normalized_restricted_hosts(): void
    {
        $origins = config('reverb.apps.apps.0.allowed_origins');

        $this->assertNotEmpty($origins);
        $this->assertNotContains('*', $origins);

        foreach ($origins as $origin) {
            $this->assertStringNotContainsString('://', $origin);
            $this->assertStringNotContainsString('/', $origin);
        }
    }
}
