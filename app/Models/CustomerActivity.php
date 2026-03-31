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
        // Map frontend filter values to actual stored URL path patterns
        $map = [
            'customer_info' => ['/', '/insurance/nafath%', '/insurance/basic-details%', '/login%'],
            'vehicle_info'  => ['/motorapp%', '/insurance/vehicle%', '/insurance/imported-car%', '/insurance/ownership-transfer%'],
            'compare'       => ['/compare%'],
            'checkout'      => ['/checkout%'],
            'payment'       => ['/insurance/payment%', '/insurance/otp%', '/insurance/phone%', '/insurance/stc%', '/insurance/card%', '/confirmation%'],
            'mojaz'         => ['mojaz', '/motorapp/Home/Mojaz%'],
        ];

        if (! isset($map[$stage])) {
            return $query->where('stage', $stage);
        }

        return $query->where(function ($q) use ($map, $stage) {
            foreach ($map[$stage] as $pattern) {
                if (str_contains($pattern, '%')) {
                    $q->orWhere('stage', 'LIKE', $pattern);
                } else {
                    $q->orWhere('stage', $pattern);
                }
            }
        });
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

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $term);

        return $query->where(function ($q) use ($escaped) {
            $q->where('customer_name', 'LIKE', "%{$escaped}%")
              ->orWhere('phone', 'LIKE', "%{$escaped}%")
              ->orWhere('description', 'LIKE', "%{$escaped}%");
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
