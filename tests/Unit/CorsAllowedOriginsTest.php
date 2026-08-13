<?php

namespace Tests\Unit;

use App\Support\CorsAllowedOrigins;
use RuntimeException;
use Tests\TestCase;

class CorsAllowedOriginsTest extends TestCase
{
    public function test_it_trims_origins_without_removing_scheme_or_port(): void
    {
        $this->assertSame(
            ['https://a.example', 'https://b.example:8443'],
            CorsAllowedOrigins::normalize('https://a.example, https://b.example:8443'),
        );
    }

    public function test_it_uses_a_trimmed_fallback_for_an_empty_value(): void
    {
        $this->assertSame(
            ['https://fallback.example'],
            CorsAllowedOrigins::normalize('  ', ' https://fallback.example '),
        );
    }

    public function test_it_rejects_wildcard_origins_with_credentials(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not contain "*"');

        CorsAllowedOrigins::normalize('https://allowed.example,*');
    }
}
