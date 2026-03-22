<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelEvent extends Model
{
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
