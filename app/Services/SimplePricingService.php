<?php

namespace App\Services;

use App\Models\Plan;

/**
 * Simple Pricing Service - Fixed company pricing contract.
 *
 * Final formula (pre-VAT):
 * base company price + comprehensive fixed gap (if applicable)
 * + deductible increase + selected addons total
 *
 * Vehicle value and risk factors must not affect price.
 */
class SimplePricingService
{
    private const VAT_RATE = 0.15; // 15% VAT in Saudi Arabia
    private const DEFAULT_DEDUCTIBLE = 1000;

    private array $config;
    private PricingSignatureService $signatureService;

    public function __construct(PricingSignatureService $signatureService)
    {
        $this->config = config('pricing');
        $this->signatureService = $signatureService;
    }

    /**
     * Calculate pricing for multiple plans using fixed pricing only.
     *
     * @param  array  $plans
     * @param  array  $vehicle  not used in pricing equation
     * @param  array  $driver   not used in pricing equation
     * @param  array  $policy   deductible/addons inputs
     * @return array
     */
    public function calculateForPlans(array $plans, array $vehicle, array $driver, array $policy): array
    {
        $addonIds = $this->normalizeAddonIds($policy['additionalCoverages'] ?? []);

        return array_map(
            fn (array $plan) => $this->calculateSinglePlan(
                $plan,
                $this->resolveEffectiveDeductible($plan, $policy),
                $addonIds
            ),
            $plans
        );
    }

    /**
     * Calculate pricing for multiple plans WITH digital signatures.
     *
     * @param  array  $plans
     * @param  array  $vehicle
     * @param  array  $driver
     * @param  array  $policy
     * @param  bool   $logCalculation
     * @return array
     */
    public function calculateForPlansWithSignature(array $plans, array $vehicle, array $driver, array $policy, bool $logCalculation = false): array
    {
        $quotes = $this->calculateForPlans($plans, $vehicle, $driver, $policy);

        return array_map(function (array $quote) {
            $planId = ! empty($quote['id'])
                ? (string) $quote['id']
                : "{$quote['companyId']}_{$quote['subType']}";

            $totalWithVATSar = (int) round($quote['totalWithVAT']);

            $sigPacket = $this->signatureService->generateSignature(
                $planId,
                $totalWithVATSar
            );

            return array_merge($quote, [
                'totalWithVATHalalas' => $totalWithVATSar * 100,
                'priceUnit'           => 'SAR',
                'signature'           => $sigPacket['signature'],
                'timestamp'           => $sigPacket['timestamp'],
                'expiresAt'           => $sigPacket['expiresAt'],
            ]);
        }, $quotes);
    }

    /**
     * Calculate lock/order amounts from company + deductible + addons.
     *
     * This method is used by checkout locking/verification paths.
     */
    public function calculateLockedTotals(
        int $companyId,
        int $deductible = self::DEFAULT_DEDUCTIBLE,
        array $addonIds = [],
        ?string $planSubType = null,
        ?string $insuranceType = null
    ): array
    {
        $basePrice = $this->resolveCompanyBasePrice($companyId);
        $comprehensiveSurcharge = $this->resolveComprehensiveSurcharge($planSubType, $insuranceType);
        $normalizedDeductible = $this->normalizeDeductible($deductible);
        $deductibleIncrease = $this->resolveDeductibleIncrease($normalizedDeductible);
        $normalizedAddonIds = $this->normalizeAddonIds($addonIds);
        $addonsBreakdown = $this->resolveAddonsBreakdown($normalizedAddonIds);

        $subtotal = round($basePrice + $comprehensiveSurcharge + $deductibleIncrease + $addonsBreakdown['addonsTotal'], 2);
        $vatAmount = round($subtotal * self::VAT_RATE, 2);
        $totalWithVAT = round($subtotal + $vatAmount, 2);

        return [
            'basePrice' => $basePrice,
            'comprehensiveSurcharge' => $comprehensiveSurcharge,
            'deductible' => $normalizedDeductible,
            'deductibleIncrease' => $deductibleIncrease,
            'addonIds' => $normalizedAddonIds,
            'addons' => $addonsBreakdown['addons'],
            'addonsTotal' => $addonsBreakdown['addonsTotal'],
            'subtotal' => $subtotal,
            'vatAmount' => $vatAmount,
            'totalWithVAT' => $totalWithVAT,
        ];
    }

    /**
     * Calculate quote amount for a single plan.
     */
    private function calculateSinglePlan(array $plan, int $effectiveDeductible, array $addonIds = []): array
    {
        $companyId = (int) ($plan['companyId'] ?? 0);
        $subType = (string) ($plan['subType'] ?? '');

        $totals = $this->calculateLockedTotals(
            $companyId,
            $effectiveDeductible,
            $addonIds,
            $subType,
            null
        );

        $annualPrice = round($totals['subtotal'], 2);
        $monthlyPrice = round($annualPrice / 12, 2);

        $priceComponents = [
            'basePrice' => $totals['basePrice'],
            'comprehensiveSurcharge' => $totals['comprehensiveSurcharge'],
            'deductibleIncrease' => $totals['deductibleIncrease'],
            'addonsTotal' => $totals['addonsTotal'],
        ];

        return [
            'id' => $plan['id'] ?? null,
            'companyId' => $companyId,
            'subType' => $subType,
            'deductible' => $totals['deductible'],
            'annualPrice' => $annualPrice,
            'monthlyPrice' => $monthlyPrice,
            'vatAmount' => $totals['vatAmount'],
            'totalWithVAT' => $totals['totalWithVAT'],
            'basePrice' => $totals['basePrice'],
            'addonsTotal' => $totals['addonsTotal'],
            'pricingFactors' => [
                'base' => 1.0,
                'vehicle' => 1.0,
                'driver' => 1.0,
                'lifestyle' => 1.0,
                'policy' => 1.0,
                'company' => 1.0,
                'ncd' => 1.0,
                'coverage' => 1.0,
                'components' => $priceComponents,
            ],
            'notes' => 'Fixed pricing contract: company base + deductible increase + addons; vehicle value ignored.',
        ];
    }

    private function resolveComprehensiveSurcharge(?string $planSubType, ?string $insuranceType): float
    {
        $isComprehensive = strcasecmp((string) $planSubType, 'comprehensive') === 0
            || strcasecmp((string) $insuranceType, 'comprehensive') === 0;

        if (! $isComprehensive) {
            return 0.0;
        }

        return (float) ($this->config['comprehensive_fixed_gap'] ?? 0);
    }

    private function resolveEffectiveDeductible(array $plan, array $policy): int
    {
        $candidate = $policy['deductible'] ?? $plan['deductible'] ?? self::DEFAULT_DEDUCTIBLE;

        return $this->normalizeDeductible((int) $candidate);
    }

    private function resolveCompanyBasePrice(int $companyId): float
    {
        $dbBasePrice = Plan::where('company_id', $companyId)->value('base_price');
        if (is_numeric($dbBasePrice)) {
            return (float) $dbBasePrice;
        }

        $configured = $this->config['fixed_company_prices'][$companyId] ?? null;

        return is_numeric($configured) ? (float) $configured : 0.0;
    }

    private function normalizeDeductible(int $deductible): int
    {
        $allowed = array_map('intval', array_keys($this->config['deductible_increase'] ?? []));
        if ($allowed === []) {
            return self::DEFAULT_DEDUCTIBLE;
        }

        return in_array($deductible, $allowed, true)
            ? $deductible
            : self::DEFAULT_DEDUCTIBLE;
    }

    private function resolveDeductibleIncrease(int $deductible): float
    {
        return (float) ($this->config['deductible_increase'][$deductible] ?? 0);
    }

    /**
     * @param  array<int|string, mixed>  $addonIds
     * @return array<int>
     */
    private function normalizeAddonIds(array $addonIds): array
    {
        $configuredIds = array_map('intval', array_keys($this->config['addons_prices'] ?? []));
        if ($configuredIds === []) {
            return [];
        }

        $normalized = [];
        foreach ($addonIds as $addonId) {
            $id = (int) $addonId;
            if (in_array($id, $configuredIds, true)) {
                $normalized[] = $id;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  array<int>  $addonIds
     * @return array{addons: array<int, array{id:int,name:string,price:float}>, addonsTotal: float}
     */
    private function resolveAddonsBreakdown(array $addonIds): array
    {
        $addonsConfig = $this->config['addons_prices'] ?? [];
        $addons = [];
        $addonsTotal = 0.0;

        foreach ($addonIds as $addonId) {
            $definition = $addonsConfig[$addonId] ?? null;
            if (! is_array($definition)) {
                continue;
            }

            $price = (float) ($definition['price'] ?? 0);
            $addons[] = [
                'id' => $addonId,
                'name' => (string) ($definition['name'] ?? ''),
                'price' => $price,
            ];
            $addonsTotal += $price;
        }

        return [
            'addons' => $addons,
            'addonsTotal' => round($addonsTotal, 2),
        ];
    }
}
