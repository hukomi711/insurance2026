<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
