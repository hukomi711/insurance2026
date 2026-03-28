<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVerification extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'ip_address',
        'phone_number',
        'otp_code',
        'verified',
        'verified_at',
        'expires_at',
        'attempts',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'verified'    => 'boolean',
        'verified_at' => 'datetime',
        'expires_at'  => 'datetime',
        'attempts'    => 'integer',
    ];

    /** @var list<string> */
    protected $hidden = [
        'otp_code',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeVerified(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('verified', true);
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('verified', false)
            ->where('expires_at', '>', now());
    }

    public function scopeForPhone(\Illuminate\Database\Eloquent\Builder $query, string $phone)
    {
        return $query->where('phone_number', $phone);
    }

    /* ── Helpers ───────────────────────────────────── */

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markVerified(): bool
    {
        return $this->update([
            'verified'    => true,
            'verified_at' => now(),
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
