<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Traits\NotifiesDashboard;
use App\Models\Order;
use App\Services\PricingSignatureService;
use App\Services\SimplePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use NotifiesDashboard;

    private PricingSignatureService $signatureService;
    private SimplePricingService $pricingService;

    public function __construct(
        PricingSignatureService $signatureService,
        SimplePricingService $pricingService
    ) {
        $this->signatureService = $signatureService;
        $this->pricingService = $pricingService;
    }

    // ─── Server-side price limits (fixed model + deductible + official addons) ─
    private const PRICE_LIMITS = [
        'third_party'   => ['min' => 499, 'max' => 3749],
        'comprehensive' => ['min' => 499, 'max' => 3749],
    ];

    private const VAT_RATE   = 0.15;
    private const TOLERANCE  = 0.02; // 2 % for floating-point rounding
    private const MAX_ADDONS = 595; // 85 + 510 in current pricing contract

    /**
     * إنشاء طلب جديد (تقديم عرض مختار)
     *
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        $supportedCompanies = array_map('intval', array_keys(config('pricing.fixed_company_prices', [])));
        $supportedDeductibles = array_map('intval', array_keys(config('pricing.deductible_increase', [])));
        $supportedAddonIds = array_map('intval', array_keys(config('pricing.addons_prices', [])));

        try {
        $validated = $request->validate([
            // Plan
            'plan_id'           => 'required|integer',
            'company_id'        => ['nullable', 'integer', Rule::in($supportedCompanies)],
            'plan_sub_type'     => 'nullable|string|in:thirdParty,thirdPartyPlus,vehicleDamagePlus,comprehensive',
            'plan_name'         => 'required|string|max:255',
            'insurance_company' => 'required|string|max:255',
            'insurance_type'    => 'required|string|in:comprehensive,third_party',
            'plan_type'         => 'nullable|string|max:100',

            // Pricing
            'subtotal'        => 'required|numeric|min:0',
            'vat_amount'      => 'required|numeric|min:0',
            'total'           => 'required|numeric|min:0',
            'deductible'      => ['nullable', 'integer', Rule::in($supportedDeductibles)],
            'addon_ids'       => 'nullable|array',
            'addon_ids.*'     => ['integer', Rule::in($supportedAddonIds)],
            'addons'          => 'nullable|array',
            'addons.*.id'     => ['nullable', 'integer', Rule::in($supportedAddonIds)],
            'pricing_factors' => 'nullable|array',

            // Applicant
            'applicant_name'        => 'nullable|string|max:255',
            'applicant_national_id' => 'nullable|string|max:20',
            'applicant_phone'       => 'nullable|string|max:20',
            'applicant_email'       => 'nullable|email|max:255',

            // Vehicle
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_make'  => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_year'  => 'nullable|integer|min:1990|max:2035',

            // Dates
            'policy_start_date' => 'nullable|date',

            // Payment
            'payment_method' => 'nullable|string|in:card,mada,visa,mastercard,tabby,tamara',
            'accept_terms' => 'required|accepted',

            // Quote lock
            'quote_lock_token' => 'nullable|string|max:64',

            // Pricing signature (anti-tampering)
            'pricing_signature' => 'nullable|string|max:255',
            'pricing_timestamp' => 'nullable|integer',
        ], [
            'accept_terms.required' => 'يجب الموافقة على الشروط والأحكام قبل المتابعة.',
            'accept_terms.accepted' => 'يجب الموافقة على الشروط والأحكام قبل المتابعة.',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Order validation failed', [
                'errors'         => $e->errors(),
                'input_keys'     => array_keys($request->all()),
                'insurance_type' => $request->input('insurance_type'),
                'plan_id'        => $request->input('plan_id'),
                'subtotal'       => $request->input('subtotal'),
                'vat_amount'     => $request->input('vat_amount'),
                'total'          => $request->input('total'),
            ]);
            throw $e;
        }

        // ── Server-side price validation ──
        $pricingError = $this->validatePricing($validated);
        if ($pricingError) {
            return response()->json([
                'success' => false,
                'message' => $pricingError,
            ], 422);
        }

        // Generate unique order and policy numbers
        $validated['order_number']  = Order::generateOrderNumber();
        $validated['policy_number'] = Order::generatePolicyNumber();

        // Calculate end date (1 year from start)
        if (!empty($validated['policy_start_date'])) {
            $validated['policy_end_date'] = \Carbon\Carbon::parse($validated['policy_start_date'])
                ->addYear()
                ->subDay()
                ->format('Y-m-d');
        }

        // Link to customer profile if session exists
        $sessionId = $request->header('X-Session-Token') ?? $request->input('session_id');
        $profile = null;
        if ($sessionId) {
            $validated['session_id'] = $sessionId;
            $profile = \App\Models\CustomerProfile::where('session_id', $sessionId)->first();
            if ($profile) {
                $validated['customer_profile_id'] = $profile->id;
            }
        }

        $order = DB::transaction(fn () => Order::create(
            collect($validated)->except(['quote_lock_token', 'addon_ids', 'accept_terms'])->all()
        ));

        // Surface the new order to the admin dashboard the same way every other
        // customer-state change does (see AdminPaymentCardController etc.) — without
        // this, orders were created silently and never reached the dashboard.
        $this->flushCustomerCache();
        if ($profile) {
            $this->notifyDashboard($profile, 'order_created');
        }

        return response()->json([
            'success'       => true,
            'order_id'      => $order->id,
            'order_number'  => $order->order_number,
            'policy_number' => $order->policy_number,
        ], 201);
    }

    // ─── Price sanity check + signature verification ───────────────────────────────────────
    private function validatePricing(array $data): ?string
    {
        $type     = $data['insurance_type'];
        $subtotal = (float) $data['subtotal'];
        $vat      = (float) $data['vat_amount'];
        $total    = (float) $data['total'];
        $planId   = $data['plan_id'] ?? null;
        $companyId = $data['company_id'] ?? null;
        $planSubType = $data['plan_sub_type'] ?? null;
        $deductible = isset($data['deductible']) ? (int) $data['deductible'] : 1000;
        $addonIds = $this->extractAddonIds($data);
        $signature = $data['pricing_signature'] ?? null;
        $timestamp = $data['pricing_timestamp'] ?? null;
        $quoteLockToken = $data['quote_lock_token'] ?? null;

        // ═══ Strongest check: server-issued quote-lock snapshot ═══
        // The lock was created by QuoteLockController from a server-side calculation
        // and stored in cache for 60 min. If the client supplies one, we require
        // an EXACT match against the snapshot — no need to recalculate from scratch.
        if ($quoteLockToken) {
            $snapshot = Cache::get('quote_lock:' . $quoteLockToken);

            if (! is_array($snapshot)) {
                Log::warning('Order pricing rejected — quote lock missing or expired', [
                    'plan_id' => $planId,
                    'token_present' => true,
                ]);

                return 'انتهت صلاحية عرض السعر. يرجى إعادة طلب عرض جديد.';
            }

            // Same insurance type (third_party vs comprehensive)?
            if (isset($snapshot['insurance_type']) && $snapshot['insurance_type'] !== $type) {
                Log::warning('Order pricing rejected — insurance_type mismatch with quote lock', [
                    'expected' => $snapshot['insurance_type'],
                    'submitted' => $type,
                ]);

                return 'نوع التأمين غير مطابق لعرض السعر المحفوظ.';
            }

            // Same plan?
            if (isset($snapshot['plan_id']) && (int) $snapshot['plan_id'] !== (int) $planId) {
                Log::warning('Order pricing rejected — plan_id mismatch with quote lock', [
                    'expected' => $snapshot['plan_id'],
                    'submitted' => $planId,
                ]);

                return 'خطة التأمين غير مطابقة لعرض السعر المحفوظ.';
            }

            if (isset($snapshot['deductible']) && (int) $snapshot['deductible'] !== $deductible) {
                Log::warning('Order pricing rejected — deductible mismatch with quote lock', [
                    'expected' => $snapshot['deductible'],
                    'submitted' => $deductible,
                ]);

                return 'قيمة التحمل غير مطابقة لعرض السعر المحفوظ.';
            }

            if (isset($snapshot['addon_ids']) && is_array($snapshot['addon_ids'])) {
                $expectedAddonIds = array_values(array_unique(array_map('intval', $snapshot['addon_ids'])));
                sort($expectedAddonIds);
                $submittedAddonIds = $addonIds;
                sort($submittedAddonIds);

                if ($expectedAddonIds !== $submittedAddonIds) {
                    Log::warning('Order pricing rejected — addon selection mismatch with quote lock', [
                        'expected' => $expectedAddonIds,
                        'submitted' => $submittedAddonIds,
                    ]);

                    return 'الإضافات المختارة لا تطابق عرض السعر المحفوظ.';
                }
            }

            // Exact amounts (1 halala tolerance for float rounding)
            foreach (['subtotal' => $subtotal, 'vat_amount' => $vat, 'total' => $total] as $field => $submitted) {
                if (! isset($snapshot[$field])) {
                    continue;
                }
                if (abs((float) $snapshot[$field] - $submitted) > 0.01) {
                    Log::warning('Order pricing rejected — amount mismatch with quote lock', [
                        'field' => $field,
                        'snapshot' => $snapshot[$field],
                        'submitted' => $submitted,
                        'plan_id' => $planId,
                    ]);

                    return 'تم تعديل السعر بعد إصدار العرض. يرجى إعادة طلب عرض جديد.';
                }
            }

            // Lock matched — the price was authenticated by /api/quotes/lock.
            // Drop through to HMAC verification below as a second defence layer.
            Log::info('Order pricing: quote lock matched', [
                'plan_id' => $planId,
                'total' => $total,
            ]);
        }

        // No quote lock token: re-calculate via fixed-pricing contract and compare.
        if (! $quoteLockToken && $companyId) {
            $recalculated = $this->pricingService->calculateLockedTotals(
                (int) $companyId,
                $deductible,
                $addonIds,
                $planSubType,
                $type === 'comprehensive' ? 'comprehensive' : null
            );

            foreach (['subtotal', 'vatAmount', 'totalWithVAT'] as $recalculatedField) {
                $submittedField = $recalculatedField === 'vatAmount'
                    ? 'vat_amount'
                    : ($recalculatedField === 'totalWithVAT' ? 'total' : 'subtotal');

                if (abs((float) $recalculated[$recalculatedField] - (float) $data[$submittedField]) > 0.01) {
                    Log::warning('Order pricing rejected — mismatch with fixed-pricing recalculation', [
                        'field' => $submittedField,
                        'expected' => $recalculated[$recalculatedField],
                        'submitted' => (float) $data[$submittedField],
                        'company_id' => $companyId,
                        'deductible' => $deductible,
                        'addon_ids' => $addonIds,
                    ]);

                    return 'الأسعار المرسلة لا تطابق آلية التسعير الثابتة.';
                }
            }
        } elseif (! $quoteLockToken && ! $companyId) {
            Log::warning('Order pricing rejected — missing company_id without quote lock', [
                'plan_id' => $planId,
            ]);

            return 'معرّف الشركة مطلوب لإتمام التحقق من السعر.';
        }

        // ═══ NEW: Verify digital signature (prevents tampering) ═══
        if ($signature && $timestamp && $planId) {
            $sigVerification = $this->signatureService->verifyPacket([
                'signature' => $signature,
                'planId' => $planId,
                'totalPrice' => (int) round($total),
                'timestamp' => (int)$timestamp,
            ]);

            // Backward compatibility: older quote signatures used "{companyId}_{subType}".
            if (!$sigVerification['valid'] && $companyId && $planSubType) {
                $legacyPlanKey = "{$companyId}_{$planSubType}";
                $sigVerification = $this->signatureService->verifyPacket([
                    'signature' => $signature,
                    'planId' => $legacyPlanKey,
                    'totalPrice' => (int) round($total),
                    'timestamp' => (int)$timestamp,
                ]);

                if ($sigVerification['valid']) {
                    Log::info('Order pricing signature validated via legacy plan key', [
                        'plan_id' => $planId,
                        'legacy_key' => $legacyPlanKey,
                    ]);
                }
            }

            if (!$sigVerification['valid']) {
                Log::warning('Order pricing rejected — signature invalid', [
                    'reason' => $sigVerification['error'] ?? 'Unknown',
                    'plan_id' => $planId,
                    'total' => $total,
                ]);
                return 'توقيع الحماية غير صحيح — قد تم تعديل السعر.';
            }
        } else {
            // No signature provided — still validate basic price range
            Log::info('Order pricing: no signature provided (valid for legacy clients)', [
                'plan_id' => $planId,
                'total' => $total,
            ]);
        }

        // 1. Subtotal within range (allow addons headroom)
        $limits = self::PRICE_LIMITS[$type] ?? null;
        if ($limits) {
            $maxAllowed = $limits['max'] + self::MAX_ADDONS;
            if ($subtotal < $limits['min'] || $subtotal > $maxAllowed) {
                Log::warning('Order pricing rejected — subtotal out of range', compact('type', 'subtotal', 'limits'));
                return 'قيمة القسط خارج النطاق المسموح.';
            }
        }

        // 2. VAT must be ~15 % of subtotal
        $expectedVat = round($subtotal * self::VAT_RATE, 2);
        if (abs($vat - $expectedVat) > $expectedVat * self::TOLERANCE + 0.01) {
            Log::warning('Order pricing rejected — VAT mismatch', compact('vat', 'expectedVat', 'subtotal'));
            return 'مبلغ الضريبة غير متطابق.';
        }

        // 3. Total must equal subtotal + VAT
        $expectedTotal = round($subtotal + $expectedVat, 2);
        if (abs($total - $expectedTotal) > $expectedTotal * self::TOLERANCE + 0.01) {
            Log::warning('Order pricing rejected — total mismatch', compact('total', 'expectedTotal'));
            return 'إجمالي الطلب غير متطابق.';
        }

        return null; // All checks passed
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int>
     */
    private function extractAddonIds(array $data): array
    {
        if (! empty($data['addon_ids']) && is_array($data['addon_ids'])) {
            return array_values(array_unique(array_map('intval', $data['addon_ids'])));
        }

        if (empty($data['addons']) || ! is_array($data['addons'])) {
            return [];
        }

        $configuredAddons = config('pricing.addons_prices', []);
        $nameToId = [];
        foreach ($configuredAddons as $configuredId => $configuredAddon) {
            $name = isset($configuredAddon['name']) ? trim((string) $configuredAddon['name']) : '';
            if ($name !== '') {
                $nameToId[$name] = (int) $configuredId;
            }
        }

        $ids = [];
        foreach ($data['addons'] as $addon) {
            if (is_array($addon) && isset($addon['id'])) {
                $ids[] = (int) $addon['id'];
                continue;
            }

            if (is_array($addon) && isset($addon['name'])) {
                $name = trim((string) $addon['name']);
                if ($name !== '' && array_key_exists($name, $nameToId)) {
                    $ids[] = $nameToId[$name];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * تعديل بيانات مقدّم الطلب أو المركبة قبل تأكيد الدفع
     *
     * يمنع تعديل السعر أو الخطة — أي تغيير في التسعير يجب أن يمر عبر
     * /api/orders (طلب جديد) لإعادة التحقق من التوقيع والسعر.
     *
     * PATCH /api/orders/{orderNumber}
     */
    public function update(Request $request, string $orderNumber): JsonResponse
    {
        $sessionId = $request->header('X-Session-Token') ?? $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'رمز الجلسة مطلوب لتعديل الطلب.',
            ], 401);
        }

        $order = Order::where('order_number', $orderNumber)
            ->where('session_id', $sessionId)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود.',
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل طلب تم تأكيده بالفعل.',
            ], 422);
        }

        $validated = $request->validate([
            'applicant_name'  => 'sometimes|nullable|string|max:255',
            'applicant_phone' => 'sometimes|nullable|string|max:20',
            'applicant_email' => 'sometimes|nullable|email|max:255',
            'vehicle_plate'   => 'sometimes|nullable|string|max:20',
            'vehicle_make'    => 'sometimes|nullable|string|max:100',
            'vehicle_model'   => 'sometimes|nullable|string|max:100',
            'vehicle_year'    => 'sometimes|nullable|integer|min:1990|max:2035',
        ]);

        if (empty($validated)) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد بيانات صالحة للتعديل.',
            ], 422);
        }

        $order->update($validated);

        // Same dashboard-sync pattern as store() — an edited order must be
        // reflected for admins in real time, not just persisted silently.
        $this->flushCustomerCache();
        if ($order->customerProfile) {
            $this->notifyDashboard($order->customerProfile, 'order_updated');
        }

        return response()->json([
            'success'      => true,
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * عرض تفاصيل طلب
     *
     * GET /api/orders/{orderNumber}
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->whereHas('customerProfile', fn ($q) => $q->where('ip_address', $request->ip()))
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'order'   => $order->only([
                'order_number', 'policy_number', 'plan_name', 'insurance_company',
                'insurance_type', 'plan_type', 'subtotal', 'vat_amount', 'total',
                'deductible', 'addons', 'vehicle_make', 'vehicle_model', 'vehicle_year',
                'policy_start_date', 'policy_end_date', 'payment_method', 'payment_status', 'status',
            ]),
        ]);
    }
}
