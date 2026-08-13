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

    public static function matches(?string $ipAddress, ?string $sessionId): bool
    {
        if (! $ipAddress && ! $sessionId) {
            return false;
        }

        foreach (self::positiveCacheKeys($ipAddress, $sessionId) as $key) {
            if (Cache::get($key) === true) {
                return true;
            }
        }

        $lookupKey = 'customer-block:lookup:'.hash('sha256', ($ipAddress ?? '').'|'.($sessionId ?? ''));

        try {
            return Cache::remember($lookupKey, now()->addMinute(), function () use ($ipAddress, $sessionId): bool {
                $blocked = self::query()
                    ->where(function ($query) use ($ipAddress, $sessionId) {
                        if ($ipAddress) {
                            $query->where('ip_address', $ipAddress);
                        }
                        if ($sessionId) {
                            $ipAddress ? $query->orWhere('session_id', $sessionId) : $query->where('session_id', $sessionId);
                        }
                    })
                    ->exists();

                if ($blocked) {
                    self::rememberBlocked($ipAddress, $sessionId);
                }

                return $blocked;
            });
        } catch (QueryException) {
            // Keep requests available during a rolling deploy before the
            // customer_blocks migration has been applied.
            return false;
        }
    }

    public static function rememberBlocked(?string $ipAddress, ?string $sessionId): void
    {
        foreach (self::positiveCacheKeys($ipAddress, $sessionId) as $key) {
            Cache::put($key, true, now()->addDays(30));
        }

        Cache::forget('customer-block:lookup:'.hash('sha256', ($ipAddress ?? '').'|'.($sessionId ?? '')));
    }

    /** @return list<string> */
    private static function positiveCacheKeys(?string $ipAddress, ?string $sessionId): array
    {
        $keys = [];
        if ($ipAddress) {
            $keys[] = 'customer-block:ip:'.hash('sha256', $ipAddress);
        }
        if ($sessionId) {
            $keys[] = 'customer-block:session:'.hash('sha256', $sessionId);
        }

        return $keys;
    }
}
