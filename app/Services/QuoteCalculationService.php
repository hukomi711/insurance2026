<?php
namespace App\Services;

use App\Models\PricingLog;
use Carbon\Carbon;

/**
 * Quote Calculation Service — SIMPLIFIED PRICING MODEL
 *
 * NEW (2026-09-18): Fixed pricing model
 * Formula: finalPrice = FIXED_COMPANY_PRICES[companyId] + DEDUCTIBLE_INCREASE[deductible] + sum(addonsPrice)
 *
 * No dynamic factors. All prices are fixed per company.
 * vehicleValue, driverAge, accidents, etc. do NOT affect final price.
 *
 * Still includes digital signatures + audit logging for compliance
 */
class QuoteCalculationService
{
    private array $config;
    private PricingSignatureService $signatureService;

    public function __construct(PricingSignatureService $signatureService)
    {
        $this->config = config('pricing');
        $this->signatureService = $signatureService;
    }

    public function calculateForPlans(array $plans, array $vehicle, array $driver, array $policy): array
    {
        return array_map(
            fn (array $plan) => $this->calculateSinglePlan($plan, $vehicle, $driver, $policy),
            $plans
        );
    }

    public function calculateForPlansWithSignature(array $plans, array $vehicle, array $driver, array $policy, bool $logCalculation = false): array
    {
        $quotes = $this->calculateForPlans($plans, $vehicle, $driver, $policy);

        return array_map(function (array $quote) use ($logCalculation) {
            $planId = !empty($quote['id']) ? (string) $quote['id'] : "{$quote['companyId']}_{$quote['subType']}";
            $totalWithVATSar = (int) $quote['totalWithVAT'];

            $sigPacket = $this->signatureService->generateSignature($planId, $totalWithVATSar);

            if ($logCalculation) {
                $this->logCalculation($quote, $planId, 'quote_calculation');
            }

            return array_merge($quote, [
                'totalWithVATHalalas' => $totalWithVATSar * 100,
                'priceUnit' => 'SAR',
                'signature' => $sigPacket['signature'],
                'timestamp' => $sigPacket['timestamp'],
                'expiresAt' => $sigPacket['expiresAt'],
            ]);
        }, $quotes);
    }

    private function logCalculation(array $quote, string|int $planId, string $context): void
    {
        try {
            $userId = null;
            if (function_exists('auth') && auth('web')->check()) {
                $userId = auth('web')->id();
            }

            PricingLog::create([
                'user_id' => $userId,
                'plan_id' => $planId,
                'quoted_price' => $quote['totalWithVAT'],
                'base_price' => $quote['basePrice'],
                'factors' => $quote['pricingFactors'],
                'pricing_version' => config('pricing.version', '1.0.0'),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'context' => $context,
                'metadata' => [
                    'annual_price' => $quote['annualPrice'],
                    'vat_amount' => $quote['vatAmount'],
                    'original_price' => $quote['originalPrice'],
                ],
            ]);
        } catch (\Exception $e) {
            logger()->warning('Failed to log pricing calculation', ['error' => $e->getMessage()]);
        }
    }

    public function calculateSinglePlan(array $plan, array $vehicle, array $driver, array $policy): array
    {
        try {
            // 1. COMPANY BASE PRICE (fixed, no factors)
            $companyId = $plan['companyId'] ?? 1;
            $fixedPrices = config('pricing.fixed_company_prices', []);
            $basePrice = $fixedPrices[$companyId] ?? 499;

            // 2. DEDUCTIBLE INCREASE
            $deductible = $policy['deductible'] ?? $plan['deductible'] ?? 1000;
            $deductibleTable = config('pricing.deductible_increase', []);
            $deductibleIncrease = $deductibleTable[$deductible] ?? 0;

            // 3. ADDONS TOTAL
            $addonsTotal = 0;
            if (!empty($policy['additionalCoverages']) && is_array($policy['additionalCoverages'])) {
                $addonsConfig = config('pricing.addons_prices', []);
                foreach ($policy['additionalCoverages'] as $addonId) {
                    $addonsTotal += $addonsConfig[$addonId]['price'] ?? 0;
                }
            }

            // 4. FINAL PRICE = base + deductible + addons
            $annualBeforeVAT = $basePrice + $deductibleIncrease + $addonsTotal;
            $annualPrice = (int) round(max(0, $annualBeforeVAT));

            $monthlyPrice = (int) ceil($annualPrice / 12);
            $vatRate = $this->config['vat_rate'] ?? 0.15;
            $vatAmount = (int) round($annualPrice * $vatRate);
            $totalWithVAT = $annualPrice + $vatAmount;

            return [
                'id' => $plan['id'] ?? null,
                'companyId' => $plan['companyId'],
                'subType' => $plan['subType'],
                'annualPrice' => $annualPrice,
                'originalPrice' => $annualPrice,
                'monthlyPrice' => $monthlyPrice,
                'vatAmount' => $vatAmount,
                'totalWithVAT' => $totalWithVAT,
                'basePrice' => $basePrice,
                'pricingFactors' => [
                    'basePrice' => $basePrice,
                    'deductibleIncrease' => $deductibleIncrease,
                    'addonsTotal' => $addonsTotal,
                    'total' => 1.0,
                ],
            ];
        } catch (\Exception $e) {
            logger()->error('[QuoteCalculationService] calculateSinglePlan failed', [
                'error' => $e->getMessage(),
                'planId' => $plan['id'] ?? null,
            ]);

            // Fallback
            $fallback = 499;
            return [
                'id' => $plan['id'] ?? null,
                'companyId' => $plan['companyId'] ?? 1,
                'subType' => $plan['subType'] ?? 'comprehensive',
                'annualPrice' => $fallback,
                'originalPrice' => $fallback,
                'monthlyPrice' => (int) ceil($fallback / 12),
                'vatAmount' => (int) round($fallback * 0.15),
                'totalWithVAT' => $fallback + (int) round($fallback * 0.15),
                'basePrice' => $fallback,
                'pricingFactors' => [
                    'basePrice' => $fallback,
                    'deductibleIncrease' => 0,
                    'addonsTotal' => 0,
                    'total' => 1.0,
                ],
            ];
        }
    }
}
