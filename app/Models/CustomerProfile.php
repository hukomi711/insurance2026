<?php

namespace App\Models;

use App\Casts\EncryptedSafe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * CustomerProfile Model
 *
 * الملف الشامل لبيانات العميل - يربط بين user_id و ip_address و session_id
 */
class CustomerProfile extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'ip_address',
        'session_id',
        'full_name',
        'phone_number',
        'phone_carrier',
        'national_id',
        'email',
        'birth_date',
        'birth_year',
        'birth_month',
        'region',
        'city',
        'vehicle_type',
        'vehicle_make',
        'vehicle_model',
        'plate_number',
        'vin',
        'manufacturing_year',
        'vehicle_price',
        'insurance_type',
        'insurance_purpose',
        'registration_type',
        'repair_method',
        'policy_start_date',
        'extra_data',
        'sequence_number',
        'customs_card',
        'has_additional_driver',
        'additional_driver_name',
        'additional_driver_national_id',
        'additional_driver_birth_date',
        'current_page',
        'completion_percentage',
        'total_visits',
        'is_active',
        'last_activity_at',
        'device_type',
        'device_browser',
        'total_price',
        'selected_insurance',
        'nafath_username',
        'nafath_password',
        'nafath_verified',
        'nafath_verification_code',
        'location_city',
        'location_country',
        'country',
        'notes',
        'assigned_admin_id',
        'journey_history',
        'journey_completion_percentage',
        'current_step',
        'total_pages_visited',
        'data_viewed',
        'otp_fail_count',
        'otp_locked_until',
        'national_id_hash',
        'phone_number_hash',
    ];

    /** @var list<string> */
    protected $hidden = [
        'national_id',
        'phone_number',
        'email',
        'nafath_username',
        'nafath_password',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'has_additional_driver' => 'boolean',
        'nafath_verified' => 'boolean',
        'national_id' => EncryptedSafe::class,
        'phone_number' => EncryptedSafe::class,
        'email' => EncryptedSafe::class,
        'nafath_username' => EncryptedSafe::class,
        'nafath_password' => EncryptedSafe::class,
        'vehicle_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'completion_percentage' => 'integer',
        'total_visits' => 'integer',
        'selected_insurance' => 'array',
        'last_activity_at' => 'datetime',
        'journey_history' => 'array',
        'journey_completion_percentage' => 'integer',
        'current_step' => 'integer',
        'total_pages_visited' => 'integer',
        'extra_data' => 'array',
        'data_viewed' => 'array',
        'otp_fail_count' => 'integer',
        'otp_locked_until' => 'datetime',
    ];

    /* ── PII Hash Helpers ───────────────────────────── */

    /**
     * Generate a deterministic SHA-256 hash for blind-index lookups.
     */
    public static function hashPii(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash('sha256', $value);
    }

    /**
     * Auto-populate hash columns whenever PII fields change.
     */
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            // national_id: read via accessor (decrypts) → hash the plaintext
            if ($model->isDirty('national_id')) {
                $plain = $model->national_id; // goes through EncryptedSafe::get()
                $model->attributes['national_id_hash'] = static::hashPii($plain);
            }

            // phone_number: same pattern
            if ($model->isDirty('phone_number')) {
                $plain = $model->phone_number;
                $model->attributes['phone_number_hash'] = static::hashPii($plain);
            }
        });
    }

    /* ── Relationships ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentCards(): HasMany
    {
        return $this->hasMany(PaymentCard::class, 'customer_profile_id');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class, 'customer_profile_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customerActivities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function funnelEvents(): HasMany
    {
        return $this->hasMany(FunnelEvent::class);
    }

    /* ── Scopes ────────────────────────────────────── */

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecentlyActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('last_activity_at', '>=', now()->subHour());
    }

    public function scopeForIp(\Illuminate\Database\Eloquent\Builder $query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeForSession(\Illuminate\Database\Eloquent\Builder $query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Exclude known bot/crawler IPs from results.
     * Covers: Googlebot (66.249.*, 66.102.*, 66.118.*).
     */
    public function scopeExcludeBots(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where(function ($q) {
            $q->where('ip_address', 'not like', '66.249.%')
              ->where('ip_address', 'not like', '66.102.%')
              ->where('ip_address', 'not like', '66.118.%');
        })->where(function ($q) {
            // Exclude profiles whose current_page is a scanner/attack path
            $q->whereNull('current_page')
              ->orWhere('current_page', '')
              ->orWhere(function ($q2) {
                  $q2->where('current_page', 'not like', '%etc/passwd%')
                     ->where('current_page', 'not like', '%wp-login%')
                     ->where('current_page', 'not like', '%wp-admin%')
                     ->where('current_page', 'not like', '%.env%')
                     ->where('current_page', 'not like', '%phpmyadmin%');
              });
        });
    }

    /* ── Static Helpers ────────────────────────────── */

    /**
     * إنشاء أو تحديث ملف العميل — بطاقة واحدة لكل عميل
     *
     * Lookup priority:
     *   1. national_id  (if provided in $data AND exists in DB) → one person = one card
     *   2. session_id   (browser token from X-Session-Token)    → same browser = same card
     *   3. ip_address   (most-recent record for this IP)        → anonymous visitors (no session token)
     *   4. Create new   (no match found)                        → first visit
     *
     * When a national_id match is found AND there is also a separate session/IP-based
     * profile, the child records (payment cards, OTPs) are merged into the national_id
     * profile and the orphan is deleted — preventing split-identity issues.
     */
    public static function createOrUpdateByIP(string $ip, array $data = []): self
    {
        // ── Resolve session_id (browser-level identity from localStorage UUID) ──
        $sessionId = $data['session_id'] ?? null;

        if (empty($sessionId) && app()->runningInConsole() === false) {
            try {
                $sessionId = request()?->header('X-Session-Token');
                if (empty($sessionId)) {
                    $sessionId = request()?->input('session_id');
                }
                if (empty($sessionId)) {
                    $sessionId = request()?->session()?->getId();
                }
            } catch (\Exception $e) {
                // Queue worker or artisan command — no request context
            }
        }

        if (! empty($sessionId)) {
            $data['session_id'] = $sessionId;
        }

        // Retry up to 3 times on deadlock (SQLSTATE 40001)
        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return DB::transaction(function () use ($ip, $data, $sessionId) {
                    return static::executeCreateOrUpdate($ip, $data, $sessionId);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt < $maxRetries && str_contains($e->getMessage(), 'Deadlock')) {
                    usleep($attempt * 50_000); // 50ms, 100ms, 150ms backoff

                    continue;
                }
                throw $e;
            }
        }

        throw new \RuntimeException('createOrUpdateByIP failed after deadlock retries');
    }

    /**
     * Logic extracted from createOrUpdateByIP for deadlock-retry wrapper.
     */
    protected static function executeCreateOrUpdate(string $ip, array $data, ?string $sessionId): self
    {
        $nationalId = $data['national_id'] ?? null;
        $customer = null;

        // ── 1. Lookup by national_id (strongest identifier) ──
        if (! empty($nationalId)) {
            $customer = self::where('national_id_hash', static::hashPii($nationalId))->lockForUpdate()->first();
        }

        // ── 2. Lookup by session_id (same browser across tabs/pages) ──
        if (! $customer && ! empty($sessionId)) {
            $customer = self::where('session_id', $sessionId)
                ->orderByDesc('last_activity_at')
                ->lockForUpdate()
                ->first();
        }

        // ── 3. Fallback: lookup by ip_address (anonymous, no session token) ──
        if (! $customer) {
            $customer = self::where('ip_address', $ip)
                ->orderByDesc('last_activity_at')
                ->lockForUpdate()
                ->first();
        }

        // ── 4. Merge child records from orphan profiles ──
        // When national_id resolves to profile A, but there's also a
        // session/IP-based profile B with payment cards or OTPs,
        // move all child records to A and delete B.
        if ($customer && ! empty($sessionId)) {
            $orphans = self::where('session_id', $sessionId)
                ->where('id', '!=', $customer->id)
                ->lockForUpdate()
                ->get();

            foreach ($orphans as $orphan) {
                // Move payment cards
                \App\Models\PaymentCard::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                // Move OTP codes
                \App\Models\OtpCode::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                // Move orders, activities, email logs, funnel events
                \App\Models\Order::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\CustomerActivity::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\EmailLog::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\FunnelEvent::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                $orphan->delete();
            }
        }

        if ($customer && ! empty($nationalId)) {
            $orphans = self::where('ip_address', $ip)
                ->where('id', '!=', $customer->id)
                ->where(function ($q) use ($nationalId) {
                    $q->whereNull('national_id_hash')
                      ->orWhere('national_id_hash', static::hashPii($nationalId));
                })
                ->lockForUpdate()
                ->get();

            foreach ($orphans as $orphan) {
                \App\Models\PaymentCard::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\OtpCode::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\Order::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\CustomerActivity::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\EmailLog::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                \App\Models\FunnelEvent::where('customer_profile_id', $orphan->id)
                    ->update(['customer_profile_id' => $customer->id]);
                $orphan->delete();
            }
        }

        // ── 5. Merge data into existing record or create new one ──

        // Sanitize current_page — strip attack/scanner paths
        if (isset($data['current_page'])) {
            $page = $data['current_page'];
            if (preg_match('#(etc/passwd|wp-login|wp-admin|\.env|phpmyadmin|\.git|xmlrpc|cgi-bin|/bin/sh)#i', $page)) {
                unset($data['current_page']);
            } else {
                $data['current_page'] = mb_substr($page, 0, 1024);
            }
        }

        $mergeData = array_merge([
            'ip_address' => $ip,
            'is_active' => true,
            'last_activity_at' => now(),
        ], $data);

        if ($customer) {
            $updateFields = array_filter($mergeData, fn ($v) => $v !== null);
            $customer->update($updateFields);
            $customer->refresh();
        } else {
            // Re-check with lock to prevent insert race: another request may have just created
            $existing = null;
            if (! empty($sessionId)) {
                $existing = self::where('session_id', $sessionId)->lockForUpdate()->first();
            }
            if (! $existing) {
                $existing = self::where('ip_address', $ip)->orderByDesc('last_activity_at')->lockForUpdate()->first();
            }

            if ($existing) {
                $updateFields = array_filter($mergeData, fn ($v) => $v !== null);
                $existing->update($updateFields);
                $customer = $existing->refresh();
            } else {
                $mergeData['total_visits'] = 1;
                $customer = self::create($mergeData);
            }
        }

        return $customer;
    }

    /* ── Domain Helpers ────────────────────────────── */

    /**
     * تحديث رحلة العميل (الصفحة الحالية والخطوة)
     */
    public function updateJourney(string $pageName, int $stepNumber): void
    {
        $this->current_page = $pageName;
        $this->current_step = max($this->current_step ?? 0, $stepNumber);

        $history = $this->journey_history ?? [];
        $history[] = [
            'page' => $pageName,
            'step' => $stepNumber,
            'timestamp' => now()->toISOString(),
        ];
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        $this->journey_history = $history;

        $this->total_pages_visited = ($this->total_pages_visited ?? 0) + 1;
        $this->journey_completion_percentage = min(100, round(($stepNumber / 7) * 100));
        $this->last_activity_at = now();
        $this->save();
    }

    /**
     * تحديث بيانات المركبة
     */
    public function updateVehicleData(array $vehicleData): void
    {
        $extra = $this->extra_data ?? [];

        if (isset($vehicleData['insurance_purpose'])) {
            $extra['insurance_purpose'] = $vehicleData['insurance_purpose'];
        }
        if (isset($vehicleData['registration_type'])) {
            $extra['registration_type'] = $vehicleData['registration_type'];
        }
        if (isset($vehicleData['owner_id'])) {
            $extra['national_id'] = $vehicleData['owner_id'];
            $this->nafath_username = $vehicleData['owner_id'];
        }
        if (isset($vehicleData['document_number'])) {
            $extra['sequence_number'] = $vehicleData['document_number'];
        }
        if (isset($vehicleData['birth_year'])) {
            $extra['birth_year'] = $vehicleData['birth_year'];
        }
        if (isset($vehicleData['birth_month'])) {
            $extra['birth_month'] = $vehicleData['birth_month'];
        }
        if (isset($vehicleData['custom_number'])) {
            $extra['customs_card'] = $vehicleData['custom_number'];
        }
        if (isset($vehicleData['year_of_manufacture'])) {
            $extra['manufacturing_year'] = $vehicleData['year_of_manufacture'];
            $this->manufacturing_year = $vehicleData['year_of_manufacture'];
        }

        $this->extra_data = $extra;

        $this->fill([
            'vehicle_type' => $vehicleData['type'] ?? $vehicleData['insurance_purpose'] ?? null,
            'vehicle_make' => $vehicleData['brand'] ?? null,
            'vehicle_model' => $vehicleData['model'] ?? null,
            'manufacturing_year' => $vehicleData['year'] ?? $vehicleData['year_of_manufacture'] ?? null,
            'plate_number' => $vehicleData['plate'] ?? $vehicleData['document_number'] ?? null,
            'vin' => $vehicleData['vin'] ?? $vehicleData['custom_number'] ?? null,
            'vehicle_price' => $vehicleData['value'] ?? null,
        ]);

        $this->last_activity_at = now();
        $this->save();

        try {
            event(new \App\Events\CustomerUpdated($this, 'vehicle'));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * تحديث بيانات التأمين
     */
    public function updateInsuranceData(array $data): void
    {
        $extra = $this->extra_data ?? [];

        if (isset($data['fullName'])) {
            $this->full_name = $data['fullName'];
            $extra['full_name'] = $data['fullName'];
            $extra['birth_date'] = $data['birthDate'] ?? null;
            $extra['region'] = $data['region'] ?? null;
            $extra['city'] = $data['city'] ?? null;
            $extra['phone_number'] = $data['phoneNumber'] ?? null;
            $extra['policy_start_date'] = $data['policyStartDate'] ?? null;
        }

        $this->fill([
            'insurance_type' => $data['insuranceType'] ?? $data['type'] ?? $this->insurance_type,
            'vehicle_model' => $data['vehicleModel'] ?? $this->vehicle_model,
            'manufacturing_year' => $data['manufacturingYear'] ?? $this->manufacturing_year,
            'plate_number' => $data['plateNumber'] ?? $this->plate_number,
            'vehicle_price' => $data['vehiclePrice'] ?? $this->vehicle_price,
        ]);

        if (isset($data['phoneNumber'])) {
            $this->phone_number = $data['phoneNumber'];
        }

        if (isset($data['repairMethod']) || isset($data['policyStartDate'])) {
            $extra['repair_method'] = $data['repairMethod'] ?? $extra['repair_method'] ?? null;
            $extra['repair_region'] = $data['repairRegion'] ?? $extra['repair_region'] ?? null;
            $extra['usage_purpose'] = $data['usagePurpose'] ?? $extra['usage_purpose'] ?? null;
            $extra['policy_start_date'] = $data['policyStartDate'] ?? $extra['policy_start_date'] ?? null;
        }

        $this->extra_data = $extra;
        $this->save();
    }

    /**
     * تحديث بيانات الدفع
     */
    public function updatePaymentData(array $paymentData): void
    {
        $extra = $this->extra_data ?? [];
        $extra['payment_method'] = $paymentData['method'] ?? null;
        $extra['payment_status'] = $paymentData['status'] ?? 'pending';
        $extra['payment_amount'] = $paymentData['amount'] ?? null;

        if (isset($paymentData['status']) && $paymentData['status'] === 'completed') {
            $extra['payment_completed_at'] = now()->toISOString();
        }

        $this->extra_data = $extra;
        $this->total_price = $paymentData['amount'] ?? $this->total_price;
        $this->save();
    }

    /**
     * تحديث بيانات العرض المختار والملخص
     */
    public function updateSelectedOfferData(array $offerData): void
    {
        $extra = $this->extra_data ?? [];

        if (isset($offerData['selectedOffer'])) {
            $offer = $offerData['selectedOffer'];
            $extra['selected_offer'] = [
                'id' => $offer['id'] ?? null,
                'name' => $offer['name'] ?? null,
                'logo' => $offer['logo'] ?? null,
                'insurance_type' => $offer['insurance_type'] ?? $offer['insuranceType'] ?? null,
                'base_price' => $offer['base_price'] ?? $offer['basePrice'] ?? null,
                'discount' => $offer['discount'] ?? 0,
                'features' => $offer['features'] ?? [],
            ];
            $this->selected_insurance = $extra['selected_offer'];
        }

        if (isset($offerData['selectedAdditions'])) {
            $extra['selected_additions'] = array_map(fn ($a) => [
                'id' => $a['id'] ?? null,
                'title' => $a['title'] ?? null,
                'description' => $a['description'] ?? null,
                'price' => $a['price'] ?? 0,
            ], $offerData['selectedAdditions']);
        }

        $extra['price_summary'] = [
            'base_price' => $offerData['basePrice'] ?? 0,
            'additions_total' => array_sum(array_column($offerData['selectedAdditions'] ?? [], 'price')),
            'subtotal' => $offerData['subtotal'] ?? 0,
            'vat' => $offerData['vat'] ?? 0,
            'total_price' => $offerData['totalPrice'] ?? 0,
        ];

        $this->total_price = $offerData['totalPrice'] ?? $this->total_price;
        $this->extra_data = $extra;
        $this->last_activity_at = now();
        $this->save();

        try {
            event(new \App\Events\CustomerUpdated($this, 'offer_selected'));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * تحديث بيانات نفاذ
     */
    public function updateNafathData(string $username, ?string $verificationCode = null): void
    {
        $this->nafath_username = $username;

        if ($verificationCode) {
            $this->nafath_verification_code = $verificationCode;
            $this->nafath_verified = true;
        }

        $this->save();
    }

    /**
     * إضافة نموذج مقدم
     */
    public function addFormSubmission(string $formType, array $formData): void
    {
        $extra = $this->extra_data ?? [];
        $submissions = $extra['form_submissions'] ?? [];

        $submissions[] = [
            'type' => $formType,
            'data' => $formData,
            'timestamp' => now()->toISOString(),
        ];

        $extra['form_submissions'] = $submissions;
        $this->extra_data = $extra;
        $this->save();
    }

    /**
     * تنظيف العملاء غير النشطين
     */
    public static function cleanupInactive(int $daysInactive = 30): int
    {
        $threshold = now()->subDays($daysInactive);

        return self::where('last_activity_at', '<', $threshold)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function markAsActive(): void
    {
        $this->update([
            'is_active' => true,
            'last_activity_at' => now(),
        ]);
    }

    public function markAsInactive(): void
    {
        $this->update(['is_active' => false]);
    }
}
