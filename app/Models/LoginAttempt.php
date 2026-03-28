<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
