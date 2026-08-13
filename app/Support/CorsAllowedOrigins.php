<?php

namespace App\Support;

use RuntimeException;

final class CorsAllowedOrigins
{
    /**
     * Normalize comma-separated CORS origins without changing their semantics.
     *
     * @return list<string>
     */
    public static function normalize(?string $value, ?string $fallback = null): array
    {
        $source = trim((string) $value) !== '' ? $value : $fallback;

        $origins = array_values(array_unique(array_filter(
            array_map('trim', explode(',', (string) $source)),
            static fn (string $origin): bool => $origin !== '',
        )));

        if (in_array('*', $origins, true)) {
            throw new RuntimeException('CORS_ALLOWED_ORIGINS must not contain "*" when credentials are enabled.');
        }

        return $origins;
    }
}
