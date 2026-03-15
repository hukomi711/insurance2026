<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $fillable = [
        'customer_ip',
        'user_id',
        'reference',
        'amount',
        'currency',
        'payment_method',
        'insurance_company',
        'policy_number',
        'status',
        'failure_reason',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'paid_at',
        'failed_at',
        'refunded_at',
        'reviewed_by',
        'reviewed_at',
        'metadata',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'gateway_response' => 'array',
        'metadata'         => 'array',
        'paid_at'          => 'datetime',
        'failed_at'        => 'datetime',
        'refunded_at'      => 'datetime',
        'reviewed_at'      => 'datetime',
    ];

    /* ── Relationships ─────────────────────────────── */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_ip', 'ip_address');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForCustomer($query, string $ip)
    {
        return $query->where('customer_ip', $ip);
    }

    /* ── Status Helpers ────────────────────────────── */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markCompleted(?string $gatewayTransactionId = null, ?array $gatewayResponse = null): bool
    {
        return $this->update([
            'status'                 => 'completed',
            'paid_at'                => now(),
            'gateway_transaction_id' => $gatewayTransactionId ?? $this->gateway_transaction_id,
            'gateway_response'       => $gatewayResponse ?? $this->gateway_response,
        ]);
    }

    public function markFailed(string $reason, ?array $gatewayResponse = null): bool
    {
        return $this->update([
            'status'           => 'failed',
            'failure_reason'   => $reason,
            'failed_at'        => now(),
            'gateway_response' => $gatewayResponse ?? $this->gateway_response,
        ]);
    }

    public function markRefunded(): bool
    {
        return $this->update([
            'status'      => 'refunded',
            'refunded_at' => now(),
        ]);
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
        ]);
    }
}
