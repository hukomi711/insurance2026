<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CustomerCacheService
{
    /**
     * Flush all admin customer list caches so the next API call returns fresh data.
     *
     * Deletes all logical keys matching: admin:customers:*
     * Redis path uses SCAN (never KEYS) and UNLINK with DEL fallback.
     */
    public static function flush(): void
    {
        $driver = config('cache.default');

        if ($driver === 'redis') {
            self::flushRedis();

            return;
        }

        if ($driver === 'database') {
            self::flushDatabase();

            return;
        }

        self::forgetCommonKeys();
    }

    private static function flushRedis(): void
    {
        try {
            $connectionName = config('cache.stores.redis.connection') ?: 'cache';
            $client = Redis::connection($connectionName)->client();

            // phpredis exposes OPT_PREFIX (Redis connection prefix); predis does not.
            $redisPrefix = '';
            if (class_exists(\Redis::class) && $client instanceof \Redis) {
                $redisPrefix = (string) ($client->getOption(\Redis::OPT_PREFIX) ?: '');
            }

            $cachePrefix = (string) config('cache.prefix', '');

            // Full Redis key shape: {redis_connection_prefix}{cache_prefix}admin:customers:*
            $pattern = $redisPrefix . $cachePrefix . 'admin:customers:*';

            $cursor = 0;
            $deleted = 0;

            do {
                $result = $client->rawCommand(
                    'SCAN',
                    (string) $cursor,
                    'MATCH',
                    $pattern,
                    'COUNT',
                    '500'
                );

                $cursor = (int) ($result[0] ?? 0);
                $keys = $result[1] ?? [];

                if (! empty($keys)) {
                    self::deleteRedisKeys($client, $keys);
                    $deleted += count($keys);
                }
            } while ($cursor !== 0);

            Log::info('Admin customer cache flushed via Redis SCAN.', [
                'pattern' => $pattern,
                'deleted' => $deleted,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Redis customer cache flush failed, falling back to common keys.', [
                'message' => $e->getMessage(),
            ]);

            self::forgetCommonKeys();
        }
    }

    /**
     * Delete a batch of Redis keys. Prefers UNLINK (non-blocking, async free)
     * and falls back to DEL on older Redis versions that don't support it.
     *
     * @param  mixed  $client
     * @param  array<int, string>  $keys
     */
    private static function deleteRedisKeys(mixed $client, array $keys): void
    {
        try {
            $client->rawCommand('UNLINK', ...$keys);
        } catch (\Throwable) {
            $client->rawCommand('DEL', ...$keys);
        }
    }

    private static function flushDatabase(): void
    {
        try {
            $cachePrefix = (string) config('cache.prefix', '');
            $table = config('cache.stores.database.table', 'cache');

            $deleted = DB::table($table)
                ->where('key', 'like', $cachePrefix . 'admin:customers:%')
                ->delete();

            Log::info('Admin customer cache flushed from database cache.', [
                'deleted' => $deleted,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Database customer cache flush failed, falling back to common keys.', [
                'message' => $e->getMessage(),
            ]);

            self::forgetCommonKeys();
        }
    }

    /**
     * Last-resort fallback when SCAN/DB flush is unavailable.
     *
     * Note: this only covers a small subset of (active × country × perPage)
     * combinations on page 1 with no search term. It will leave stale entries
     * for paginated/searched variants — the SCAN path is preferred.
     */
    private static function forgetCommonKeys(): void
    {
        $countries = ['', 'SA', 'other', 'OTHER'];
        $perPages = [25, 50, 100];

        foreach (['0', '1'] as $active) {
            foreach ($countries as $country) {
                foreach ($perPages as $perPage) {
                    Cache::forget("admin:customers:{$active}::{$country}:1:{$perPage}");
                }
            }
        }
    }
}
