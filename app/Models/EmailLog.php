<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $customer_profile_id
 * @property string $email
 * @property string $type
 * @property string|null $funnel_step
 * @property string $subject
 * @property string $status
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property int $open_count
 * @property int $click_count
 * @property string|null $session_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerProfile|null $customerProfile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog clicked()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog opened()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog sent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereClickCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereClickedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereFunnelStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereOpenCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class EmailLog extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'customer_profile_id',
        'email',
        'type',
        'funnel_step',
        'subject',
        'status',
        'failure_reason',
        'sent_at',
        'opened_at',
        'clicked_at',
        'open_count',
        'click_count',
        'session_id',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata'   => 'array',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'sent_at'    => 'datetime',
        'opened_at'  => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public const TYPE_ABANDONED = 'abandoned';
    public const TYPE_WELCOME   = 'welcome';
    public const TYPE_REMINDER  = 'reminder';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    /* ── Relationships ─────────────────────────────── */

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeSent(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeOpened(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNotNull('clicked_at');
    }

    /* ── Rate Limit Check ──────────────────────────── */

    /**
     * Check if we already sent too many emails to this address today.
     */
    public static function dailyLimitReached(string $email, int $maxPerDay = 3): bool
    {
        return static::where('email', $email)
            ->where('status', self::STATUS_SENT)
            ->where('sent_at', '>=', now()->startOfDay())
            ->count() >= $maxPerDay;
    }

    /**
     * Check if an abandoned email was already sent for this session + step.
     */
    public static function alreadySentForStep(string $sessionId, string $step): bool
    {
        return static::where('session_id', $sessionId)
            ->where('funnel_step', $step)
            ->where('type', self::TYPE_ABANDONED)
            ->where('status', self::STATUS_SENT)
            ->exists();
    }
}
