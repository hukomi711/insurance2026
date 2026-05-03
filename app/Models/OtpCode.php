<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $customer_profile_id
 * @property string|null $session_id
 * @property string $type
 * @property string|null $code
 * @property string|null $code_hash
 * @property string|null $code_value
 * @property string|null $phone_number
 * @property string $status
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerProfile $customer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereCodeHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereCodeValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OtpCode whereVerifiedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class OtpCode extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $hidden = [
        'code',
        'code_hash',
    ];

    /** @var list<string> */
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

    /** @var array<string, string> */
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

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, string $type)
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
