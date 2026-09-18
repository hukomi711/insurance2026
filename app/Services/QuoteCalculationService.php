<?php

namespace App\Services;

/**
 * @deprecated Compatibility shim. Use SimplePricingService directly.
 *
 * This class intentionally delegates to SimplePricingService so no legacy
 * dynamic pricing path can diverge from the fixed pricing contract.
 */
class QuoteCalculationService
{
    public function __construct(
        private SimplePricingService $simplePricingService
    ) {}

    public function calculateForPlans(array $plans, array $vehicle, array $driver, array $policy): array
    {
        return $this->simplePricingService->calculateForPlans($plans, $vehicle, $driver, $policy);
    }

    public function calculateForPlansWithSignature(array $plans, array $vehicle, array $driver, array $policy, bool $logCalculation = false): array
    {
        return $this->simplePricingService->calculateForPlansWithSignature($plans, $vehicle, $driver, $policy, $logCalculation);
    }

    public function calculateSinglePlan(array $plan, array $vehicle, array $driver, array $policy): array
    {
        $quotes = $this->simplePricingService->calculateForPlans([$plan], $vehicle, $driver, $policy);

        return $quotes[0] ?? [
            'id' => $plan['id'] ?? null,
            'companyId' => $plan['companyId'] ?? 0,
            'subType' => $plan['subType'] ?? 'comprehensive',
            'deductible' => $plan['deductible'] ?? 1000,
            'annualPrice' => 0.0,
            'monthlyPrice' => 0.0,
            'vatAmount' => 0.0,
            'totalWithVAT' => 0.0,
            'basePrice' => 0.0,
            'addonsTotal' => 0.0,
            'pricingFactors' => [
                'base' => 1.0,
                'vehicle' => 1.0,
                'driver' => 1.0,
                'lifestyle' => 1.0,
                'policy' => 1.0,
                'company' => 1.0,
                'ncd' => 1.0,
                'coverage' => 1.0,
            ],
            'notes' => 'Fixed pricing shim fallback.',
        ];
    }
}
