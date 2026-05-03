<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $event_name
 * @property string $session_id
 * @property int|null $customer_profile_id
 * @property string|null $quote_uuid
 * @property string|null $step_name
 * @property int|null $step_order
 * @property string|null $previous_step
 * @property string|null $device_type
 * @property string|null $source
 * @property string|null $campaign
 * @property string|null $country
 * @property bool $is_returning_user
 * @property int|null $elapsed_seconds
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerProfile|null $customerProfile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereCampaign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereElapsedSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereEventName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereIsReturningUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereOccurredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent wherePreviousStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereQuoteUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereStepName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FunnelEvent whereUserAgent($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class FunnelEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'event_name',
        'session_id',
        'customer_profile_id',
        'quote_uuid',
        'step_name',
        'step_order',
        'previous_step',
        'device_type',
        'source',
        'campaign',
        'country',
        'is_returning_user',
        'elapsed_seconds',
        'metadata',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata'          => 'array',
        'is_returning_user' => 'boolean',
        'elapsed_seconds'   => 'integer',
        'step_order'        => 'integer',
        'occurred_at'       => 'datetime',
    ];

    /**
     * Allowed event names — validated before insert.
     */
    public const ALLOWED_EVENTS = [
        // Funnel step lifecycle
        'funnel_step_viewed',
        'funnel_step_completed',
        'funnel_step_abandoned',

        // OTP-specific
        'otp_requested',
        'otp_resent',
        'otp_expired',
        'otp_verified',

        // Payment
        'payment_wait_started',
        'payment_wait_completed',
        'payment_failed',
        'payment_rejected_viewed',
        'payment_rejected_retry_clicked',
        'payment_rejected_edit_clicked',

        // Order
        'quote_selected',
        'checkout_submitted',
        'order_confirmed',
    ];

    /**
     * Funnel steps with their ordinal positions.
     */
    public const STEP_ORDER = [
        'compare'          => 0,
        'checkout'         => 1,
        'payment_waiting'  => 2,
        'otp'              => 3,
        'card_pin'         => 4,
        'phone_verification' => 5,
        'confirmation'     => 6,
    ];

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }
}
