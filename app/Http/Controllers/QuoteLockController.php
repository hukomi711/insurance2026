<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteLockRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QuoteLockController extends Controller
{
    private const LOCK_TTL_MINUTES = 15;

    /**
     * Issue a short-lived quote lock token for checkout consistency.
     *
     * POST /api/quotes/lock
     */
    public function store(StoreQuoteLockRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $addons = $validated['addons'] ?? [];
        $addonsTotal = collect($addons)->sum(fn ($a) => (float) ($a['price'] ?? 0));

        $token = (string) Str::ulid();
        $expiresAt = now()->addMinutes(self::LOCK_TTL_MINUTES);

        Cache::put('quote_lock:' . $token, [
            'plan_id'           => (int) $validated['plan_id'],
            'plan_name'         => $validated['plan_name'],
            'insurance_company' => $validated['insurance_company'],
            'insurance_type'    => $validated['insurance_type'],
            'plan_type'         => $validated['plan_type'] ?? null,
            'subtotal'          => round((float) $validated['subtotal'], 2),
            'vat_amount'        => round((float) $validated['vat_amount'], 2),
            'total'             => round((float) $validated['total'], 2),
            'deductible'        => isset($validated['deductible']) ? (int) $validated['deductible'] : null,
            'addons_total'      => round((float) $addonsTotal, 2),
            'session_id'        => $validated['session_id'] ?? null,
            'issued_ip'         => $request->ip(),
            'expires_at'        => $expiresAt->toIso8601String(),
        ], $expiresAt);

        return response()->json([
            'success' => true,
            'quote_lock_token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'ttl_minutes' => self::LOCK_TTL_MINUTES,
        ]);
    }
}
