<?php

namespace App\Services;

use App\Models\Plan;

/**
 * Simple Pricing Service - Fixed Prices Per Plan
 *
 * Replaces the complex factor-based QuoteCalculationService.
 * Each plan has a fixed base price stored in the database.
 *
 * Formula: totalWithVAT = basePrice × (1 + VAT_RATE)
 */
class SimplePricingService
{
    private const VAT_RATE = 0.15; // 15% VAT in Saudi Arabia
    private array $config;
    private PricingSignatureService $signatureService;

    public function __construct(PricingSignatureService $signatureService)
    {
        $this->config = config('pricing');
        $this->signatureService = $signatureService;
    }

    /**
     * Calculate pricing for multiple plans using fixed prices.
     *
     * @param  array  $plans    [{ id?, companyId, subType (thirdParty|comprehensive), deductible }, ...]
     * @param  array  $vehicle  (not used for fixed pricing)
     * @param  array  $driver   (not used for fixed pricing)
     * @param  array  $policy   (not used for fixed pricing)
     * @return array  [ { id?, companyId, subType, annualPrice, monthlyPrice, vatAmount, totalWithVAT, basePrice, signature, timestamp }, ... ]
     */
    public function calculateForPlans(array $plans, array $vehicle, array $driver, array $policy): array
    {
        return array_map(
            fn (array $plan) => $this->calculateSinglePlan($plan),
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

            $totalWithVATSar = (int) $quote['totalWithVAT'];

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
     * Calculate price for a single plan using fixed base price.
     */
    private function calculateSinglePlan(array $plan): array
    {
        // Look up the fixed base price for this plan
        $dbPlan = Plan::where('company_id', $plan['companyId'])
            ->where('sub_type', $plan['subType'])
            ->first();

        // Fallback to config base prices if not found
        $basePrice = $dbPlan
            ? $dbPlan->base_price
            : ($this->config['base_premiums'][$plan['subType']] ?? 1000);

        $basePrice = (float) $basePrice;
        $annualPrice = round($basePrice, 2);
        $monthlyPrice = round($annualPrice / 12, 2);
        $vatAmount = round($annualPrice * self::VAT_RATE, 2);
        $totalWithVAT = round($annualPrice + $vatAmount, 2);

        return [
            'id' => $plan['id'] ?? null,
            'companyId' => $plan['companyId'],
            'subType' => $plan['subType'],
            'deductible' => $plan['deductible'] ?? 0,
            'annualPrice' => $annualPrice,
            'monthlyPrice' => $monthlyPrice,
            'vatAmount' => $vatAmount,
            'totalWithVAT' => $totalWithVAT,
            'basePrice' => $basePrice,
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
            'notes' => 'Fixed pricing — no dynamic factors applied.',
        ];
    }
}
