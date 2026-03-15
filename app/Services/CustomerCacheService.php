<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CustomerCacheService
{
    /**
     * Flush all admin customer list caches so the next API call returns fresh data.
     * Uses raw Redis SCAN to delete all admin:customers:* keys (any page, search, perPage, country).
     */
    public static function flush(): void
    {
        $driver = config('cache.default');

        if ($driver === 'redis') {
            try {
                $client = \Illuminate\Support\Facades\Redis::connection(
                    config('cache.stores.redis.connection', 'cache')
                )->client();

                // Build the FULL Redis key pattern including all prefixes.
                // phpredis OPT_PREFIX = Redis connection prefix (e.g. "insurance-2026-database-")
                // cache.prefix = Laravel cache prefix (e.g. "insurance-2026-cache-")
                $redisPrefix = $client->getOption(\Redis::OPT_PREFIX) ?: '';
                $cachePrefix = config('cache.prefix', '');
                $pattern = $redisPrefix . $cachePrefix . 'admin:customers:*';

                // Use rawCommand to bypass phpredis prefix auto-prepend
                $cursor = 0;
                do {
                    $result = $client->rawCommand('SCAN', $cursor, 'MATCH', $pattern, 'COUNT', 200);
                    $cursor = (int) ($result[0] ?? 0);
                    $keys = $result[1] ?? [];
                    if (!empty($keys)) {
                        $client->rawCommand('DEL', ...$keys);
                    }
                } while ($cursor !== 0);
            } catch (\Throwable $e) {
                \Log::warning('Redis SCAN flush failed, falling back to Cache::forget: ' . $e->getMessage());
                self::forgetCommonKeys();
            }
        } elseif ($driver === 'database') {
            try {
                $cachePrefix = config('cache.prefix', '');
                \Illuminate\Support\Facades\DB::table(
                    config('cache.stores.database.table', 'cache')
                )->where('key', 'like', $cachePrefix . 'admin:customers:%')->delete();
            } catch (\Throwable $e) {
                \Log::warning('DB cache flush failed: ' . $e->getMessage());
                self::forgetCommonKeys();
            }
        } else {
            self::forgetCommonKeys();
        }
    }

    /**
     * Fallback: forget the most common cache key variants.
     */
    private static function forgetCommonKeys(): void
    {
        $countries = ['', 'SA', 'other', 'OTHER'];
        foreach (['0', '1'] as $active) {
            foreach ($countries as $country) {
                Cache::forget("admin:customers:{$active}::{$country}:1:50");
            }
        }
    }
}
