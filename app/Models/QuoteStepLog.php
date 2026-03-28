<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteStepLog extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'quote_session_id',
        'step_name',
        'step_number',
        'entered_at',
        'exited_at',
        'duration_seconds',
        'form_snapshot',
        'exit_reason',
        'interaction_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'form_snapshot' => 'array',
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
        'duration_seconds' => 'integer',
        'interaction_count' => 'integer',
        'step_number' => 'integer',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function quoteSession(): BelongsTo
    {
        return $this->belongsTo(QuoteSession::class);
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Close this step log (user navigated away)
     */
    public function close(string $exitReason = 'next', ?array $formSnapshot = null): void
    {
        $duration = $this->entered_at
            ? (int) abs(now()->diffInSeconds($this->entered_at))
            : 0;

        $this->update([
            'exited_at' => now(),
            'duration_seconds' => $duration,
            'exit_reason' => $exitReason,
            'form_snapshot' => $formSnapshot ?? $this->form_snapshot,
        ]);
    }
}
