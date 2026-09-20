<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CustomerCacheService
{
    private const REGISTRY_KEY = 'admin:customers:key_registry';

    /**
     * Keep track of dynamically generated dashboard list cache keys so they can
     * be invalidated reliably on stores that do not support prefix scans.
     */
    public static function trackKey(string $key): void
    {
        try {
            $keys = Cache::get(self::REGISTRY_KEY, []);
            if (! is_array($keys)) {
                $keys = [];
            }

            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }

            // Bound growth; newest keys are most relevant.
            if (count($keys) > 500) {
                $keys = array_slice($keys, -500);
            }

            Cache::forever(self::REGISTRY_KEY, $keys);
        } catch (\Throwable $e) {
            Log::debug('Customer cache key tracking skipped.', ['message' => $e->getMessage()]);
        }
    }

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
            self::forgetTrackedKeys();
            self::forgetAdminNotificationKeys();

            return;
        }

        if ($driver === 'database') {
            self::flushDatabase();
            self::forgetTrackedKeys();
            self::forgetAdminNotificationKeys();

            return;
        }

        self::forgetTrackedKeys();
        self::forgetCommonKeys();
        self::forgetAdminNotificationKeys();
    }

    /**
     * Forget all cache keys discovered via trackKey().
     */
    private static function forgetTrackedKeys(): void
    {
        try {
            $keys = Cache::get(self::REGISTRY_KEY, []);

            if (is_array($keys)) {
                foreach ($keys as $key) {
                    if (is_string($key) && $key !== '') {
                        Cache::forget($key);
                    }
                }
            }

            Cache::forget(self::REGISTRY_KEY);
        } catch (\Throwable $e) {
            Log::debug('Tracked customer cache flush skipped.', ['message' => $e->getMessage()]);
        }
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
     * Note: this is a compatibility fallback for legacy key shapes and common
     * page-1 combinations. Dynamic keys are handled by trackKey()/forgetTrackedKeys().
     */
    private static function forgetCommonKeys(): void
    {
        $countries = ['', 'SA', 'other', 'OTHER'];
        $perPages = [25, 50, 80, 100, 150];

        // Legacy key shape (pre-v6)
        foreach (['0', '1'] as $active) {
            foreach ($countries as $country) {
                foreach ($perPages as $perPage) {
                    Cache::forget("admin:customers:{$active}::{$country}:1:{$perPage}");
                }
            }
        }

        // Current key shape: admin:customers:saudi:v6:{active}:{payment}:{search}:{page}:{perPage}:{sortBy}:{sortOrder}
        foreach (['0', '1'] as $active) {
            foreach (['0', '1'] as $paymentOnly) {
                foreach ($perPages as $perPage) {
                    foreach (['last_activity_at', 'created_at'] as $sortBy) {
                        foreach (['asc', 'desc'] as $sortOrder) {
                            Cache::forget("admin:customers:saudi:v6:{$active}:{$paymentOnly}::1:{$perPage}:{$sortBy}:{$sortOrder}");
                        }
                    }
                }
            }
        }

        Cache::forget(self::REGISTRY_KEY);
    }

    /**
     * Notification and badge caches are fed by the same pending OTP/card data
     * that changes when customer/admin actions flush the dashboard list.
     */
    private static function forgetAdminNotificationKeys(): void
    {
        Cache::forget('admin:notifications:raw');
        Cache::forget('admin:badge_counts');
    }
}
