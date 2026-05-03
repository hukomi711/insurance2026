<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $email
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $location المدينة، البلد
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt recent(int $hours = 24)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt search(?string $term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt successful()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginAttempt whereUserId($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class LoginAttempt extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'location',
        'status',
    ];

    // ─── العلاقات ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeSuccessful(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $term);

        return $query->where(function ($q) use ($escaped) {
            $q->where('email', 'LIKE', "%{$escaped}%")
              ->orWhere('ip_address', 'LIKE', "%{$escaped}%")
              ->orWhere('location', 'LIKE', "%{$escaped}%");
        });
    }

    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // ─── Factory Method ─────────────────────────────────────────────

    /**
     * Record a login attempt.
     */
    public static function record(string $email, string $ip, ?string $userAgent, string $status, ?int $userId = null): self
    {
        return static::create([
            'user_id'    => $userId,
            'email'      => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'location'   => null, // يمكن تفعيل GeoIP لاحقاً
            'status'     => $status,
        ]);
    }
}
