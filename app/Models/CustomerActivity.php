<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerActivity extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'customer_profile_id',
        'customer_name',
        'phone',
        'stage',
        'activity_type',
        'description',
        'status',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
    ];

    // ─── العلاقات ────────────────────────────────────────────────────

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOfStage(\Illuminate\Database\Eloquent\Builder $query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('customer_name', 'LIKE', "%{$term}%")
              ->orWhere('phone', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function getStageLabel(): string
    {
        return match ($this->stage) {
            'customer_info' => 'بيانات العميل',
            'vehicle_info'  => 'بيانات المركبة',
            'compare'       => 'مقارنة العروض',
            'checkout'      => 'إتمام الطلب',
            'payment'       => 'الدفع',
            default         => $this->stage,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active'    => 'نشط',
            'completed' => 'مكتمل',
            'failed'    => 'فشل',
            default     => $this->status,
        };
    }
}
