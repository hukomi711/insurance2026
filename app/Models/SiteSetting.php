<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    private const CACHE_KEY = 'site_settings:all';
    private const CACHE_TTL = 3600;

    /**
     * Get a setting value by key, with cache.
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        $all = self::allCached();
        return $all[$key] ?? $default;
    }

    /**
     * Get every setting as an associative array, cached.
     *
     * @return array<string, mixed>
     */
    public static function allCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn ($row) => [$row->key => self::castValue($row->value, $row->type)])
                ->toArray();
        });
    }

    /**
     * Set/update a key.
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'type' => $type, 'group' => $group],
        );
        self::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function castValue(?string $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }
        return match ($type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'json' => json_decode($raw, true) ?? [],
            default => $raw,
        };
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }
}
