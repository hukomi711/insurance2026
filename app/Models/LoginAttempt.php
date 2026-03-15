<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
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

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('email', 'LIKE', "%{$term}%")
              ->orWhere('ip_address', 'LIKE', "%{$term}%")
              ->orWhere('location', 'LIKE', "%{$term}%");
        });
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // ─── Factory Method ─────────────────────────────────────────────

    /**
     * Record a login attempt.
     */
    public static function record(string $email, string $ip, ?string $userAgent, string $status, ?int $userId = null): static
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
