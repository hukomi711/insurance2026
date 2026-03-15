<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVerification extends Model
{
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

    protected $casts = [
        'verified'    => 'boolean',
        'verified_at' => 'datetime',
        'expires_at'  => 'datetime',
        'attempts'    => 'integer',
    ];

    protected $hidden = [
        'otp_code',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopePending($query)
    {
        return $query->where('verified', false)
            ->where('expires_at', '>', now());
    }

    public function scopeForPhone($query, string $phone)
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
