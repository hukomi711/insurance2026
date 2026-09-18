<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteLockRequest;
use App\Models\Plan;
use App\Services\PricingSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QuoteLockController extends Controller
{
    private const LOCK_TTL_MINUTES = 60;
    private const VAT_RATE = 0.15;
    private const MAX_ADDONS = 4000; // sanity cap; addons aren't part of the fixed-plan price

    public function __construct(
        private PricingSignatureService $signatureService
    ) {}

    /**
     * Issue a short-lived quote lock token for checkout consistency.
     *
     * POST /api/quotes/lock
     */
    public function store(StoreQuoteLockRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $addons = $validated['addons'] ?? [];
        $addonsTotal = min(
            self::MAX_ADDONS,
            collect($addons)->sum(fn ($a) => (float) ($a['price'] ?? 0))
        );

        // Never trust client-submitted subtotal/vat/total — recompute from the
        // authoritative fixed price (Plan model, falling back to config).
        $dbPlan = Plan::where('company_id', $validated['company_id'])
            ->where('sub_type', $validated['plan_sub_type'])
            ->first();
        $basePrice = $dbPlan
            ? (float) $dbPlan->base_price
            : (float) (config('pricing.base_premiums')[$validated['plan_sub_type']] ?? 1000);

        $subtotal = round($basePrice + $addonsTotal, 2);
        $vatAmount = round($subtotal * self::VAT_RATE, 2);
        $total = round($subtotal + $vatAmount, 2);

        $token = (string) Str::ulid();
        $expiresAt = now()->addMinutes(self::LOCK_TTL_MINUTES);
        $signedTotal = (int) round($total);
        $signaturePacket = $this->signatureService->generateSignature(
            (int) $validated['plan_id'],
            $signedTotal
        );

        Cache::put('quote_lock:' . $token, [
            'plan_id'           => (int) $validated['plan_id'],
            'company_id'        => (int) $validated['company_id'],
            'plan_sub_type'     => $validated['plan_sub_type'],
            'plan_name'         => $validated['plan_name'],
            'insurance_company' => $validated['insurance_company'],
            'insurance_type'    => $validated['insurance_type'],
            'plan_type'         => $validated['plan_type'] ?? null,
            'subtotal'          => $subtotal,
            'vat_amount'        => $vatAmount,
            'total'             => $total,
            'deductible'        => isset($validated['deductible']) ? (int) $validated['deductible'] : null,
            'addons_total'      => round($addonsTotal, 2),
            'session_id'        => $validated['session_id'] ?? null,
            'issued_ip'         => $request->ip(),
            'expires_at'        => $expiresAt->toIso8601String(),
        ], $expiresAt);

        return response()->json([
            'success' => true,
            'quote_lock_token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'ttl_minutes' => self::LOCK_TTL_MINUTES,
            'pricing_signature' => $signaturePacket['signature'],
            'pricing_timestamp' => $signaturePacket['timestamp'],
            'pricing_expires_at' => $signaturePacket['expiresAt'],
            // Authoritative amounts — the client must display these, not its own computation.
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'total' => $total,
        ]);
    }
}
