<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class CustomerBlock extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'ip_address',
        'session_id',
        'customer_profile_id',
        'blocked_by',
    ];

    public static function matches(?string $sessionId): bool
    {
        if (! $sessionId) {
            return false;
        }

        if (Cache::get(self::sessionCacheKey($sessionId)) === true) {
            return true;
        }

        // Keep this key separate from the former IP-and-session lookup key so
        // an old cached IP match cannot continue blocking shared networks.
        $lookupKey = 'customer-block:lookup:session:'.hash('sha256', $sessionId);

        try {
            return Cache::remember($lookupKey, now()->addMinute(), function () use ($sessionId): bool {
                $blocked = self::query()
                    ->where('session_id', $sessionId)
                    ->exists();

                if ($blocked) {
                    self::rememberBlocked($sessionId);
                }

                return $blocked;
            });
        } catch (QueryException) {
            // Keep requests available during a rolling deploy before the
            // customer_blocks migration has been applied.
            return false;
        }
    }

    public static function rememberBlocked(string $sessionId): void
    {
        Cache::put(self::sessionCacheKey($sessionId), true, now()->addDays(30));

        Cache::forget('customer-block:lookup:session:'.hash('sha256', $sessionId));
    }

    public static function forgetBlocked(string $sessionId): void
    {
        Cache::forget(self::sessionCacheKey($sessionId));
        Cache::forget('customer-block:lookup:session:'.hash('sha256', $sessionId));
    }

    private static function sessionCacheKey(string $sessionId): string
    {
        return 'customer-block:session:'.hash('sha256', $sessionId);
    }
}
