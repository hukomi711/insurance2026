<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_number',
        'policy_number',
        'customer_profile_id',
        'session_id',
        'plan_id',
        'plan_name',
        'insurance_company',
        'insurance_type',
        'plan_type',
        'subtotal',
        'vat_amount',
        'total',
        'deductible',
        'addons',
        'pricing_factors',
        'applicant_name',
        'applicant_national_id',
        'applicant_phone',
        'applicant_email',
        'vehicle_plate',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'policy_start_date',
        'policy_end_date',
        'payment_method',
        'payment_status',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'subtotal'          => 'decimal:2',
        'vat_amount'        => 'decimal:2',
        'total'             => 'decimal:2',
        'addons'            => 'array',
        'pricing_factors'   => 'array',
        'policy_start_date' => 'date:Y-m-d',
        'policy_end_date'   => 'date:Y-m-d',
    ];

    // ─── العلاقات ────────────────────────────────────────────────

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'confirmed');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * توليد رقم طلب تلقائي (thread-safe)
     */
    public static function generateOrderNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->format('Y');
            $last = static::where('order_number', 'LIKE', "ORD-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('order_number');

            if ($last) {
                $lastNum = (int) str_replace("ORD-{$year}-", '', $last);
                $next    = $lastNum + 1;
            } else {
                $next = 1001;
            }

            return sprintf('ORD-%s-%04d', $year, $next);
        });
    }

    /**
     * توليد رقم وثيقة تلقائي عند تأكيد الطلب
     */
    public static function generatePolicyNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->format('Y');
            $last = static::where('policy_number', 'LIKE', "POL-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('policy_number');

            if ($last) {
                $lastNum = (int) str_replace("POL-{$year}-", '', $last);
                $next    = $lastNum + 1;
            } else {
                $next = 1001;
            }

            return sprintf('POL-%s-%04d', $year, $next);
        });
    }
}
