<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // ─── Server-side price limits (mirrors pricingConstants.js) ────
    private const PRICE_LIMITS = [
        'third_party'   => ['min' => 500,  'max' => 2000],
        'comprehensive' => ['min' => 1800, 'max' => 8000],
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
        $validated = $request->validate([
            // Plan
            'plan_id'           => 'required|integer',
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
            'payment_method' => 'nullable|string|in:card,tabby,tamara',

            // Quote lock
            'quote_lock_token' => 'required|string|max:64',
        ]);

        // ── Validate quote lock token before price checks ──
        $lockError = $this->validateQuoteLock($validated, $request);
        if ($lockError) {
            return response()->json([
                'success' => false,
                'message' => $lockError,
            ], 422);
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
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');
        if ($sessionId) {
            $validated['session_id'] = $sessionId;
            $profile = \App\Models\CustomerProfile::where('session_id', $sessionId)->first();
            if ($profile) {
                $validated['customer_profile_id'] = $profile->id;
            }
        }

        $order = DB::transaction(fn () => Order::create($validated));

        // One-time token usage — consume after successful order creation
        Cache::forget('quote_lock:' . $validated['quote_lock_token']);

        return response()->json([
            'success'       => true,
            'order_id'      => $order->id,
            'order_number'  => $order->order_number,
            'policy_number' => $order->policy_number,
        ], 201);
    }

    // ─── Price sanity check ───────────────────────────────────────
    private function validatePricing(array $data): ?string
    {
        $type     = $data['insurance_type'];
        $subtotal = (float) $data['subtotal'];
        $vat      = (float) $data['vat_amount'];
        $total    = (float) $data['total'];

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

    // ─── Quote lock consistency check ─────────────────────────────
    private function validateQuoteLock(array $data, Request $request): ?string
    {
        $token = $data['quote_lock_token'] ?? null;
        if (! $token) {
            return 'رمز تثبيت السعر مفقود.';
        }

        $lock = Cache::get('quote_lock:' . $token);
        if (! is_array($lock)) {
            return 'انتهت صلاحية العرض. يرجى العودة لصفحة المقارنة وتحديث الأسعار.';
        }

        // IP guard (same requester)
        if (($lock['issued_ip'] ?? null) && $lock['issued_ip'] !== $request->ip()) {
            return 'تعذّر التحقق من العرض المثبت. يرجى إعادة اختيار العرض.';
        }

        // Session guard when available
        $incomingSession = $request->header('X-Session-ID') ?? $data['session_id'] ?? null;
        if (! empty($lock['session_id']) && ! empty($incomingSession) && $lock['session_id'] !== $incomingSession) {
            return 'العرض المثبت لا يطابق الجلسة الحالية.';
        }

        // Core plan matching
        if ((int) $lock['plan_id'] !== (int) $data['plan_id']) {
            return 'العرض المثبت لا يطابق الخطة المختارة.';
        }

        if (($lock['insurance_type'] ?? null) !== ($data['insurance_type'] ?? null)) {
            return 'نوع التأمين لا يطابق العرض المثبت.';
        }

        if (($lock['deductible'] ?? null) !== ($data['deductible'] ?? null)) {
            return 'قيمة التحمل لا تطابق العرض المثبت.';
        }

        // Price matching (strict-ish with tiny tolerance for decimals)
        $subtotal = round((float) $data['subtotal'], 2);
        $vat      = round((float) $data['vat_amount'], 2);
        $total    = round((float) $data['total'], 2);

        if (abs($subtotal - (float) ($lock['subtotal'] ?? 0)) > 0.01) {
            return 'تم تغيير سعر الوثيقة. يرجى تحديث العرض قبل الإكمال.';
        }

        if (abs($vat - (float) ($lock['vat_amount'] ?? 0)) > 0.01) {
            return 'تم تغيير الضريبة. يرجى تحديث العرض قبل الإكمال.';
        }

        if (abs($total - (float) ($lock['total'] ?? 0)) > 0.01) {
            return 'تم تغيير الإجمالي. يرجى تحديث العرض قبل الإكمال.';
        }

        return null;
    }

    /**
     * عرض تفاصيل طلب
     *
     * GET /api/orders/{orderNumber}
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return response()->json([
            'success' => true,
            'order'   => $order,
        ]);
    }
}
