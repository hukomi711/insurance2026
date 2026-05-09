<?php

namespace App\Services;

use App\Models\PricingLog;
use Carbon\Carbon;

/**
 * Quote Calculation Service
 *
 * 1:1 PHP port of resources/js/utils/pricingEngine.js
 * Uses config/pricing.php for all factor constants.
 *
 * Same formula: rawPrice = basePrice × vehicle × driver × lifestyle × policy × company × ncd
 * Then clamp to PRICE_LIMITS, round to nearest 10.
 *
 * NEW: Includes digital signatures + audit logging
 */
class QuoteCalculationService
{
    private array $config;
    private PricingSignatureService $signatureService;

    public function __construct(
        PricingSignatureService $signatureService
    ) {
        $this->config = config('pricing');
        $this->signatureService = $signatureService;
    }

    /**
     * Calculate pricing for multiple plans (batch).
     *
        * @param  array  $plans    [{ id?, companyId, subType, deductible }, ...]
     * @param  array  $vehicle  { year, make, estimatedValue, purposeOfUse, carModification, hasTrailer, transmissionType }
     * @param  array  $driver   { dateOfBirth, drivingExperience, accidentCounts, trafficViolations, education, foreignLicense, healthConditions, ncdYears, city, nightParking, expectedKM, additionalDrivers }
     * @param  array  $policy   { repairMethod, deductible? }
        * @return array  [ { id?, companyId, subType, annualPrice, monthlyPrice, vatAmount, totalWithVAT, basePrice, pricingFactors, notes, signature, timestamp }, ... ]
     */
    public function calculateForPlans(array $plans, array $vehicle, array $driver, array $policy): array
    {
        return array_map(
            fn (array $plan) => $this->calculateSinglePlan($plan, $vehicle, $driver, $policy),
            $plans
        );
    }

    /**
     * Calculate pricing for multiple plans WITH digital signatures (for checkout).
     *
     * @param  array  $plans
     * @param  array  $vehicle
     * @param  array  $driver
     * @param  array  $policy
     * @param  bool   $withSignature  - If true, includes digital signature
     * @param  bool   $logCalculation - If true, logs to PricingLog table
     * @return array  [ { ...quote, signature, timestamp }, ... ]
     */
    public function calculateForPlansWithSignature(array $plans, array $vehicle, array $driver, array $policy, bool $logCalculation = true): array
    {
        $quotes = $this->calculateForPlans($plans, $vehicle, $driver, $policy);

        return array_map(function (array $quote) use ($logCalculation) {
            // Generate signature for this quote
            $planId = !empty($quote['id']) ? (int) $quote['id'] : "{$quote['companyId']}_{$quote['subType']}";
            $sigPacket = $this->signatureService->generateSignature($planId, (int) $quote['totalWithVAT']);

            // Log this calculation
            if ($logCalculation) {
                $this->logCalculation($quote, $planId, 'quote_calculation');
            }

            return array_merge($quote, [
                'signature' => $sigPacket['signature'],
                'timestamp' => $sigPacket['timestamp'],
                'expiresAt' => $sigPacket['expiresAt'],
            ]);
        }, $quotes);
    }

    /**
     * Log a pricing calculation for audit trail.
     */
    private function logCalculation(array $quote, string $planId, string $context): void
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
            // Log error but don't break pricing calculation
            logger()->warning('Failed to log pricing calculation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate pricing for a single plan.
     */
    public function calculateSinglePlan(array $plan, array $vehicle, array $driver, array $policy): array
    {
        $basePremiums = $this->config['base_premiums'];
        $basePrice = $basePremiums[$plan['subType']] ?? 800;

        // Risk factors
        $vehicleFactor   = $this->getVehicleRiskFactor($vehicle);
        $driverFactor    = $this->getDriverRiskFactor($driver);
        $lifestyleFactor = $this->getLifestyleRiskFactor($driver);

        // Policy factor — policy.deductible overrides plan.deductible
        $effectiveDeductible = $policy['deductible'] ?? $plan['deductible'];
        $policyFactor = $this->getPolicyFactor($policy, $effectiveDeductible);

        // Company factor
        $companyFactors = $this->config['company_pricing_factors'];
        $companyFactor = $companyFactors[$plan['companyId']] ?? 1.0;

        // NCD factor
        $ncdFactor = $this->getNcdFactor($driver['ncdYears'] ?? null);

        // Coverage limit factor (comprehensive, vehicleDamagePlus, thirdPartyPlus)
        $coverageFactor = $this->getCoverageLimitFactor(
            $policy['coverageLimit'] ?? null,
            $plan['subType'] ?? null
        );

        // Raw price
        $rawPrice = $basePrice * $vehicleFactor * $driverFactor * $lifestyleFactor
                    * $policyFactor * $companyFactor * $ncdFactor * $coverageFactor;

        // Clamp to limits
        $limits = $this->config['price_limits'][$plan['subType']] ?? ['min' => 500, 'max' => 8000];
        $clampedPrice = max($limits['min'], min($limits['max'], $rawPrice));

        // Round to nearest 10
        $annualBeforeDiscount = (int) (round($clampedPrice / 10) * 10);

        // 20% promotional discount
        $annualPrice  = (int) (round(($annualBeforeDiscount * 0.80) / 10) * 10);
        $originalPrice = $annualBeforeDiscount;

        $monthlyPrice = (int) ceil($annualPrice / 12);
        $vatRate      = $this->config['vat_rate'];
        $vatAmount    = (int) round($annualPrice * $vatRate);
        $totalWithVAT = $annualPrice + $vatAmount;

        // Notes for nullable fields
        $notes = [];
        if (empty($driver['drivingExperience'])) {
            $notes['drivingExperience'] = 'neutral (missing)';
        }
        if (empty($driver['ncdYears']) && ($driver['ncdYears'] ?? null) !== '0') {
            $notes['ncdYears'] = 'neutral (missing)';
        } else {
            $notes['ncdYears'] = 'applied';
        }

        return [
            'id'             => $plan['id'] ?? null,
            'companyId'      => $plan['companyId'],
            'subType'        => $plan['subType'],
            'annualPrice'    => $annualPrice,
            'originalPrice'  => $originalPrice,
            'monthlyPrice'   => $monthlyPrice,
            'vatAmount'      => $vatAmount,
            'totalWithVAT'   => $totalWithVAT,
            'basePrice'      => $basePrice,
            'pricingFactors' => [
                'vehicle'   => round($vehicleFactor, 3),
                'driver'    => round($driverFactor, 3),
                'lifestyle' => round($lifestyleFactor, 3),
                'policy'    => round($policyFactor, 3),
                'company'   => $companyFactor,
                'ncd'       => $ncdFactor,
                'coverage'  => round($coverageFactor, 3),
                'total'     => round(
                    $vehicleFactor * $driverFactor * $lifestyleFactor
                    * $policyFactor * $companyFactor * $ncdFactor * $coverageFactor,
                    3
                ),
            ],
            'notes' => $notes,
        ];
    }

    // ═══════════════════════════════════════════════
    //  Vehicle Risk Factors
    // ═══════════════════════════════════════════════

    private function getVehicleRiskFactor(array $v): float
    {
        return $this->getVehicleAgeFactor($v['year'] ?? null)
             * $this->getManufacturerFactor($v['make'] ?? null)
             * $this->getVehicleValueFactor($v['estimatedValue'] ?? null)
             * $this->getPurposeFactor($v['purposeOfUse'] ?? null)
             * $this->getModificationFactor($v['carModification'] ?? 'no')
             * $this->getTrailerFactor($v['hasTrailer'] ?? 'no')
             * $this->getTransmissionFactor($v['transmissionType'] ?? null);
    }

    private function getVehicleAgeFactor(mixed $year): float
    {
        if (!$year) return 1.0;
        $currentYear = (int) date('Y');
        $age = $currentYear - (int) $year;
        foreach ($this->config['vehicle_age_factors'] as $entry) {
            if ($age <= $entry['maxAge']) {
                return $entry['factor'];
            }
        }
        return 1.0;
    }

    private function getManufacturerFactor(mixed $makeId): float
    {
        $manufacturerFactors = $this->config['manufacturer_factors'] ?? [];
        if (empty($manufacturerFactors) || !$makeId) {
            return 1.0;
        }
        return $manufacturerFactors[(string) $makeId] ?? 1.0;
    }

    private function getVehicleValueFactor(mixed $value): float
    {
        if (!$value) return 1.0;
        $val = (int) $value;
        foreach ($this->config['vehicle_value_factors'] as $entry) {
            if ($val <= $entry['maxValue']) {
                return $entry['factor'];
            }
        }
        return 1.0;
    }

    private function getPurposeFactor(mixed $purpose): float
    {
        return $this->config['purpose_factors'][$purpose] ?? 1.0;
    }

    private function getModificationFactor(string $hasModification): float
    {
        return $hasModification === 'yes' ? $this->config['modification_factor'] : 1.0;
    }

    private function getTrailerFactor(string $hasTrailer): float
    {
        return $hasTrailer === 'yes' ? $this->config['trailer_factor'] : 1.0;
    }

    private function getTransmissionFactor(mixed $type): float
    {
        $transmissionFactors = $this->config['transmission_factors'] ?? [];
        if (empty($transmissionFactors) || !$type) {
            return 1.0;
        }
        return $transmissionFactors[(string) $type] ?? 1.0;
    }

    // ═══════════════════════════════════════════════
    //  Driver Risk Factors
    // ═══════════════════════════════════════════════

    private function getDriverRiskFactor(array $d): float
    {
        return $this->getDriverAgeFactor($d['dateOfBirth'] ?? null)
             * $this->getExperienceFactor($d['drivingExperience'] ?? null)
             * $this->getAccidentFactor($d['accidentCounts'] ?? '0')
             * $this->getViolationFactor($d['trafficViolations'] ?? 'no')
             * $this->getEducationFactor($d['education'] ?? null)
             * $this->getForeignLicenseFactor($d['foreignLicense'] ?? 'no')
             * $this->getHealthConditionFactor($d['healthConditions'] ?? 'no')
             * $this->getAdditionalDriversFactor($d['additionalDrivers'] ?? []);
    }

    private function getDriverAgeFactor(mixed $dateOfBirth): float
    {
        if (!$dateOfBirth) return 1.0;

        try {
            $dob = Carbon::parse($dateOfBirth);
            $age = $dob->age;
        } catch (\Exception) {
            return 1.0;
        }

        foreach ($this->config['driver_age_factors'] as $entry) {
            if ($age <= $entry['maxAge']) {
                return $entry['factor'];
            }
        }
        return 1.0;
    }

    private function getExperienceFactor(mixed $experience): float
    {
        $experienceFactors = $this->config['experience_factors'] ?? [];
        if (empty($experienceFactors) || $experience === null || $experience === '') {
            return 1.0;
        }
        return $experienceFactors[(string) $experience] ?? 1.0;
    }

    private function getAccidentFactor(mixed $count): float
    {
        return $this->config['accident_factors'][(string) $count] ?? 1.0;
    }

    private function getViolationFactor(mixed $violations): float
    {
        return $this->config['violation_factors'][$violations] ?? 1.0;
    }

    private function getEducationFactor(mixed $education): float
    {
        if (!$education) return 1.0;
        return $this->config['education_factors'][(string) $education] ?? 1.0;
    }

    private function getForeignLicenseFactor(string $foreignLicense): float
    {
        return $foreignLicense === 'yes' ? $this->config['foreign_license_factor'] : 1.0;
    }

    private function getHealthConditionFactor(string $healthConditions): float
    {
        return $healthConditions === 'yes' ? $this->config['health_condition_factor'] : 1.0;
    }

    private function getAdditionalDriversFactor(?array $drivers): float
    {
        if (!is_array($drivers) || count($drivers) === 0) return 1.0;
        return pow($this->config['additional_driver_factor'], count($drivers));
    }

    // ═══════════════════════════════════════════════
    //  Lifestyle Risk Factors
    // ═══════════════════════════════════════════════

    private function getLifestyleRiskFactor(array $d): float
    {
        return $this->getCityFactor($d['city'] ?? null)
             * $this->getParkingFactor($d['nightParking'] ?? null)
             * $this->getMileageFactor($d['expectedKM'] ?? null);
    }

    private function getCityFactor(mixed $city): float
    {
        if (!$city) return 1.0;
        return $this->config['city_factors'][$city]
            ?? $this->config['city_factors']['_default'];
    }

    private function getParkingFactor(mixed $parking): float
    {
        return $this->config['parking_factors'][(string) $parking] ?? 1.0;
    }

    private function getMileageFactor(mixed $mileage): float
    {
        return $this->config['mileage_factors'][(string) $mileage] ?? 1.0;
    }

    // ═══════════════════════════════════════════════
    //  Policy Factors
    // ═══════════════════════════════════════════════

    private function getPolicyFactor(array $policy, mixed $deductible): float
    {
        return $this->getDeductibleFactor($deductible)
             * $this->getRepairMethodFactor($policy['repairMethod'] ?? 'workshop');
    }

    private function getDeductibleFactor(mixed $deductible): float
    {
        if ($deductible === null || $deductible === '') return 1.0;

        $val = (int) $deductible;
        $factors = $this->config['deductible_factors'];

        if (isset($factors[$val])) return $factors[$val];

        // Find closest key
        $keys = array_keys($factors);
        $closest = $keys[0];
        foreach ($keys as $key) {
            if (abs($key - $val) < abs($closest - $val)) {
                $closest = $key;
            }
        }
        return $factors[$closest] ?? 1.0;
    }

    private function getRepairMethodFactor(string $method): float
    {
        return $this->config['repair_method_factors'][$method] ?? 1.0;
    }

    // ═══════════════════════════════════════════════
    //  NCD Factor
    // ═══════════════════════════════════════════════

    private function getNcdFactor(mixed $ncdYears): float
    {
        if ($ncdYears === null || $ncdYears === '') return 1.0;
        return $this->config['ncd_factors'][(string) $ncdYears] ?? 1.0;
    }

    // ═══════════════════════════════════════════════
    //  Coverage Limit Factor
    // ═══════════════════════════════════════════════

    private function getCoverageLimitFactor(mixed $coverageLimit, ?string $planType): float
    {
        $affectedTypes = ['comprehensive', 'vehicleDamagePlus', 'thirdPartyPlus'];
        if (!in_array($planType, $affectedTypes, true) || !$coverageLimit) {
            return 1.0;
        }
        $val = (int) $coverageLimit;
        foreach ($this->config['coverage_limit_factors'] as $entry) {
            if ($val <= $entry['maxValue']) {
                return $entry['factor'];
            }
        }
        return 1.0;
    }
}
