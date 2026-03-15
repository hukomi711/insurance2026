<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class OtpCode extends Model
{
    use HasFactory;

    protected $hidden = [
        'code',
        'code_hash',
    ];

    protected $fillable = [
        'customer_profile_id',
        'session_id',
        'type',
        'code',
        'code_hash',
        'code_value',
        'phone_number',
        'status',
        'rejection_reason',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /* ── Encryption Accessor / Mutator ───────────────── */

    /**
     * Decrypt the OTP code on read.
     * Falls back to plaintext for legacy rows that were stored before encryption.
     */
    public function getCodeAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Legacy plaintext row — return as-is
            return $value;
        }
    }

    /**
     * Encrypt the OTP code on write.
     */
    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value !== null
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Check if the stored code is still legacy plaintext (not encrypted).
     */
    public function isLegacyPlaintext(): bool
    {
        $raw = $this->attributes['code'] ?? null;

        if ($raw === null) {
            return false;
        }

        // Encrypted payloads produced by Laravel Crypt always start with 'eyJ'
        return ! str_starts_with($raw, 'eyJ');
    }

    /**
     * Re-encrypt a legacy plaintext code in-place.
     */
    public function reencryptIfNeeded(): bool
    {
        if (! $this->isLegacyPlaintext()) {
            return false;
        }

        $plaintext = $this->attributes['code'];
        $this->attributes['code'] = Crypt::encryptString($plaintext);
        return $this->saveQuietly();
    }

    /* ── Relationships ─────────────────────────────── */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /* ── Helpers ───────────────────────────────────── */

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function verify(): bool
    {
        return $this->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function reject(?string $reason = null): bool
    {
        return $this->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
