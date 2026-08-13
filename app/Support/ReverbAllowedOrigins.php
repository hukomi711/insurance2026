<?php

namespace App\Support;

use RuntimeException;

final class ReverbAllowedOrigins
{
    /**
     * @return list<string>
     */
    public static function normalize(?string $value, bool $production): array
    {
        if (trim((string) $value) === '') {
            if ($production) {
                throw new RuntimeException('REVERB_ALLOWED_ORIGINS must not be empty in production.');
            }

            return ['localhost', '127.0.0.1'];
        }

        $origins = [];

        foreach (explode(',', $value) as $entry) {
            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            if ($entry === '*') {
                if ($production) {
                    throw new RuntimeException('REVERB_ALLOWED_ORIGINS must not contain "*" in production.');
                }

                $origins[] = '*';

                continue;
            }

            $url = str_contains($entry, '://') ? $entry : "https://{$entry}";
            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                throw new RuntimeException("Invalid REVERB_ALLOWED_ORIGINS entry: {$entry}");
            }

            $host = strtolower(rtrim($host, '.'));
            $validationHost = str_starts_with($host, '*.') ? substr($host, 2) : $host;
            $isIp = filter_var($validationHost, FILTER_VALIDATE_IP) !== false;
            $isHostname = filter_var($validationHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;

            if (! $isIp && ! $isHostname) {
                throw new RuntimeException("Invalid REVERB_ALLOWED_ORIGINS entry: {$entry}");
            }

            $origins[] = $host;
        }

        $origins = array_values(array_unique($origins));

        if ($origins === []) {
            if ($production) {
                throw new RuntimeException('REVERB_ALLOWED_ORIGINS must contain at least one valid hostname in production.');
            }

            return ['localhost', '127.0.0.1'];
        }

        return $origins;
    }
}
