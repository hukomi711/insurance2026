<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteLockRequest;
use App\Services\PricingSignatureService;
use App\Services\SimplePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QuoteLockController extends Controller
{
    private const LOCK_TTL_MINUTES = 60;

    public function __construct(
        private PricingSignatureService $signatureService,
        private SimplePricingService $pricingService
    ) {}

    /**
     * Issue a short-lived quote lock token for checkout consistency.
     *
     * POST /api/quotes/lock
     */
    public function store(StoreQuoteLockRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $addonIds = $this->extractAddonIds($validated);
        $deductible = isset($validated['deductible']) ? (int) $validated['deductible'] : 1000;

        $totals = $this->pricingService->calculateLockedTotals(
            (int) $validated['company_id'],
            $deductible,
            $addonIds,
            $validated['plan_sub_type'] ?? null,
            $validated['insurance_type'] ?? null
        );

        $subtotal = $totals['subtotal'];
        $vatAmount = $totals['vatAmount'];
        $total = $totals['totalWithVAT'];

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
            'base_price'        => $totals['basePrice'],
            'subtotal'          => $subtotal,
            'vat_amount'        => $vatAmount,
            'total'             => $total,
            'deductible'        => $totals['deductible'],
            'deductible_increase' => $totals['deductibleIncrease'],
            'addon_ids'         => $totals['addonIds'],
            'addons'            => $totals['addons'],
            'addons_total'      => round($totals['addonsTotal'], 2),
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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int>
     */
    private function extractAddonIds(array $validated): array
    {
        if (! empty($validated['addon_ids']) && is_array($validated['addon_ids'])) {
            return array_values(array_unique(array_map('intval', $validated['addon_ids'])));
        }

        // Backward compatibility: older clients send full addon objects.
        if (empty($validated['addons']) || ! is_array($validated['addons'])) {
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
        foreach ($validated['addons'] as $addon) {
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
}
