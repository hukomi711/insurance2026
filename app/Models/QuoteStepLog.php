<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quote_session_id
 * @property string $step_name
 * @property int $step_number
 * @property \Illuminate\Support\Carbon $entered_at
 * @property \Illuminate\Support\Carbon|null $exited_at
 * @property int $duration_seconds
 * @property array<array-key, mixed>|null $form_snapshot
 * @property string|null $exit_reason
 * @property int $interaction_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\QuoteSession $quoteSession
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereDurationSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereEnteredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereExitReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereExitedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereFormSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereInteractionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereQuoteSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereStepName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereStepNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuoteStepLog whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
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
