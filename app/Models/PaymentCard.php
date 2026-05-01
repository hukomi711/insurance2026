<?php

namespace App\Models;

use App\Casts\EncryptedSafe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentCard Model
 *
 * @property int $id
 * @property int $customer_profile_id
 * @property string|null $session_id
 * @property string|null $card_number رقم البطاقة الكامل (مشفّر تلقائياً عبر encrypted cast)
 * @property string|null $card_number_masked
 * @property string|null $last4
 * @property string|null $holder_name
 * @property string|null $card_type
 * @property string|null $expiry_month
 * @property string|null $expiry_year
 * @property string $status
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $redirect_url
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PaymentCard extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $hidden = [
        'card_number',
    ];

    /** @var list<string> */
    protected $fillable = [
        'customer_profile_id',
        'session_id',
        'card_number',         // مشفّر تلقائياً عبر encrypted cast
        'card_number_masked',
        'last4',
        'holder_name',
        'card_type',
        'expiry_month',
        'expiry_year',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'redirect_url',
    ];

    /** @var array<string, string|class-string> */
    protected $casts = [
        'card_number'  => EncryptedSafe::class,
        'expiry_month' => EncryptedSafe::class,
        'expiry_year'  => EncryptedSafe::class,
        'reviewed_at'  => 'datetime',
    ];

    /**
     * Append computed attributes to JSON
     */
    /** @var list<string> */
    protected $appends = [
        'card_display',
    ];

    /**
     * Get masked card number for safe display.
     */
    public function getCardDisplayAttribute(): ?string
    {
        if ($this->last4) {
            return '**** **** **** ' . $this->last4;
        }

        return $this->card_number_masked ?? '**** **** **** ****';
    }

    /* ── Relationships ─────────────────────────────── */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    /**
     * Alias for customer relationship
     */
    public function customerProfile(): BelongsTo
    {
        return $this->customer();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'rejected');
    }

    /* ── Status Helpers ────────────────────────────── */

    public function approve(?int $reviewedBy = null): bool
    {
        return $this->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(string $reason, ?int $reviewedBy = null): bool
    {
        return $this->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by'      => $reviewedBy,
            'reviewed_at'      => now(),
        ]);
    }
}
