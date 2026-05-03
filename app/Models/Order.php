<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $order_number
 * @property string|null $policy_number
 * @property int|null $customer_profile_id
 * @property string|null $session_id
 * @property int|null $plan_id
 * @property string|null $plan_name
 * @property string|null $insurance_company
 * @property string|null $insurance_type
 * @property string|null $plan_type
 * @property numeric $subtotal
 * @property numeric $vat_amount
 * @property numeric $total
 * @property int $deductible
 * @property array<array-key, mixed>|null $addons
 * @property array<array-key, mixed>|null $pricing_factors
 * @property string|null $applicant_name
 * @property string|null $applicant_national_id
 * @property string|null $applicant_phone
 * @property string|null $applicant_email
 * @property string|null $vehicle_plate
 * @property string|null $vehicle_make
 * @property string|null $vehicle_model
 * @property int|null $vehicle_year
 * @property \Illuminate\Support\Carbon|null $policy_start_date
 * @property \Illuminate\Support\Carbon|null $policy_end_date
 * @property string|null $payment_method
 * @property string $payment_status
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CustomerProfile|null $customerProfile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAddons($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereApplicantEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereApplicantName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereApplicantNationalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereApplicantPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeductible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereInsuranceCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereInsuranceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePlanName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePlanType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePolicyEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePolicyNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePolicyStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePricingFactors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereVatAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereVehicleMake($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereVehicleModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereVehiclePlate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereVehicleYear($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
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
