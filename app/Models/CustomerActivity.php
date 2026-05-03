<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $customer_profile_id
 * @property string $customer_name اسم العميل
 * @property string|null $phone
 * @property string $stage customer_info / vehicle_info / compare / checkout / payment
 * @property string $activity_type page_view / form_fill / compare_plans / select_plan / filter / upload / payment_complete / payment_failed
 * @property string $description وصف النشاط
 * @property string $status
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerProfile|null $customerProfile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity ofStage(string $stage)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity recent(int $minutes = 60)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity search(?string $term)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereActivityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomerActivity whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
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
