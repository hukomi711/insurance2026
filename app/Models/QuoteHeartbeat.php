<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quote_session_id
 * @property string $current_step
 * @property string|null $customer_ip
 * @property bool $tab_visible
 * @property \Illuminate\Support\Carbon $pinged_at
 * @property-read \App\Models\QuoteSession $quoteSession
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat whereCurrentStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat whereCustomerIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat wherePingedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat whereQuoteSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteHeartbeat whereTabVisible($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class QuoteHeartbeat extends Model
{
    /** @var bool */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'quote_session_id',
        'current_step',
        'customer_ip',
        'tab_visible',
        'pinged_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tab_visible' => 'boolean',
        'pinged_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function quoteSession(): BelongsTo
    {
        return $this->belongsTo(QuoteSession::class);
    }
}
