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
 * @property string|null $last4
 * @property string|null $holder_name
 * @property string|null $card_type
 * @property string|null $expiry_month
 * @property string|null $expiry_year
 * @property string|null $cvv_encrypted CVV مشفّر ودائم في التخزين (انحراف عن PCI-DSS 3.3.1 بطلب صريح من الجهة المعنيّة)
 * @property string $status
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $redirect_url
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\CustomerProfile $customer
 * @property-read \App\Models\CustomerProfile $customerProfile
 * @property-read string|null $card_display
 * @property-read \App\Models\User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereCardNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereCardType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereExpiryMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereExpiryYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereHolderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereLast4($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentCard whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PaymentCard extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $hidden = [
        'card_number',
        'cvv_encrypted',
    ];

    /** @var list<string> */
    protected $fillable = [
        'customer_profile_id',
        'session_id',
        'card_number',         // مشفّر تلقائياً عبر encrypted cast
        'last4',
        'holder_name',
        'card_type',
        'expiry_month',
        'expiry_year',
        'cvv_encrypted',       // مشفّر — تخزين دائم بطلب صريح (غير متوافق PCI-DSS)
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'redirect_url',
        // Resolver-derived columns (populated by PaymentCardObserver / backfill)
        'bin_8',
        'bin_6',
        'detected_bank_key',
        'detected_network',
        'detected_secondary_network',
        'detected_type',
        'detected_level',
        'detection_confidence',
        'detection_match_type',
    ];

    /** @var array<string, string|class-string> */
    protected $casts = [
        'card_number'    => EncryptedSafe::class,
        'expiry_month'   => EncryptedSafe::class,
        'expiry_year'    => EncryptedSafe::class,
        'cvv_encrypted'  => EncryptedSafe::class,
        'reviewed_at'    => 'datetime',
    ];

    /**
     * Append computed attributes to JSON
     */
    /** @var list<string> */
    protected $appends = [
        'card_display',
    ];

    /**
     * Get full card number for admin display (PAN persisted by business decision).
     */
    public function getCardDisplayAttribute(): ?string
    {
        return $this->card_number ?: $this->last4;
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
