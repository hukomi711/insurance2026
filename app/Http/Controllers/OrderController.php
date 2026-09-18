<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PricingSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private PricingSignatureService $signatureService;

    public function __construct(
        PricingSignatureService $signatureService
    ) {
        $this->signatureService = $signatureService;
    }

    // ─── Server-side price limits (fixed pricing: 399/499 SAR + addons headroom) ─
    private const PRICE_LIMITS = [
        'third_party'   => ['min' => 399, 'max' => 2499],
        'comprehensive' => ['min' => 499, 'max' => 6499],
    ];

    private const VAT_RATE   = 0.15;
    private const TOLERANCE  = 0.02; // 2 % for floating-point rounding
    private const MAX_ADDONS = 4000; // max SAR addons can add to subtotal

    /**
     * إنشاء طلب جديد (تقديم عرض مختار)
     *
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        try {
        $validated = $request->validate([
            // Plan
            'plan_id'           => 'required|integer',
            'company_id'        => 'nullable|integer',
            'plan_sub_type'     => 'nullable|string|in:thirdParty,comprehensive',
            'plan_name'         => 'required|string|max:255',
            'insurance_company' => 'required|string|max:255',
            'insurance_type'    => 'required|string|in:comprehensive,third_party',
            'plan_type'         => 'nullable|string|max:100',

            // Pricing
            'subtotal'        => 'required|numeric|min:0',
            'vat_amount'      => 'required|numeric|min:0',
            'total'           => 'required|numeric|min:0',
            'deductible'      => 'nullable|integer|min:0',
            'addons'          => 'nullable|array',
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

            // Quote lock
            'quote_lock_token' => 'nullable|string|max:64',

            // Pricing signature (anti-tampering)
            'pricing_signature' => 'nullable|string|max:255',
            'pricing_timestamp' => 'nullable|integer',
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
        if ($sessionId) {
            $validated['session_id'] = $sessionId;
            $profile = \App\Models\CustomerProfile::where('session_id', $sessionId)->first();
            if ($profile) {
                $validated['customer_profile_id'] = $profile->id;
            }
        }

        $order = DB::transaction(fn () => Order::create(collect($validated)->except(['quote_lock_token'])->all()));

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

        // ═══ NEW: Verify digital signature (prevents tampering) ═══
        if ($signature && $timestamp && $planId) {
            $sigVerification = $this->signatureService->verifyPacket([
                'signature' => $signature,
                'planId' => $planId,
                'totalPrice' => (int)$total,
                'timestamp' => (int)$timestamp,
            ]);

            // Backward compatibility: older quote signatures used "{companyId}_{subType}".
            if (!$sigVerification['valid'] && $companyId && $planSubType) {
                $legacyPlanKey = "{$companyId}_{$planSubType}";
                $sigVerification = $this->signatureService->verifyPacket([
                    'signature' => $signature,
                    'planId' => $legacyPlanKey,
                    'totalPrice' => (int)$total,
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
