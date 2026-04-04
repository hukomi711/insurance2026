<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // ─── Server-side price limits (post-20% discount, mirrors pricingConstants.js) ─
    private const PRICE_LIMITS = [
        'third_party'   => ['min' => 300,  'max' => 2500],
        'comprehensive' => ['min' => 450,  'max' => 7500],
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
            'payment_method' => 'nullable|string|in:card,mada,visa,mastercard,tabby,tamara',

            // Quote lock
            'quote_lock_token' => 'nullable|string|max:64',
        ]);

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
