<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $page
 * @property string $action
 * @property string|null $device_type
 * @property string|null $device_browser
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity forIp(string $ip)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity ofAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity recent(int $minutes = 60)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereDeviceBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity wherePage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserActivity whereUserId($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class UserActivity extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'ip_address',
        'page',
        'action',
        'device_type',
        'device_browser',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForIp(\Illuminate\Database\Eloquent\Builder $query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeOfAction(\Illuminate\Database\Eloquent\Builder $query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
