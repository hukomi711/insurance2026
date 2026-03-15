<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteHeartbeat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'quote_session_id',
        'current_step',
        'customer_ip',
        'tab_visible',
        'pinged_at',
    ];

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
