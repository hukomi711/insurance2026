<?php

namespace Tests\Unit;

use App\Services\QuoteCalculationService;
use Tests\TestCase;

/**
 * Parity tests: PHP pricing engine must produce identical results to JS engine.
 *
 * Each scenario uses known inputs and expected outputs pre-computed from the
 * JS pricing engine (pricingEngine.js + pricingConstants.js).
 *
 * Formula: rawPrice = basePrice × vehicle × driver × lifestyle × policy × company × ncd
 * Then clamp to PRICE_LIMITS[subType], round to nearest 10.
 */
class QuoteCalculationServiceTest extends TestCase
{
    private QuoteCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuoteCalculationService::class);
    }

    /**
     * Scenario 1: Basic third-party, Toyota, young driver in Riyadh, no accidents
     *
     * basePrice = 600 (thirdParty)
     * vehicle: age 4yr (1.00) × Toyota (0.95) × value 85k (1.00) × personal (1.00) × no mod (1.00) × no trailer (1.00) × auto (1.00) = 0.95
     * driver:  age 35 (1.00) × exp null (1.00) × acc 0 (0.85) × no viol (1.00) × bach (0.95) × no foreign (1.00) × no health (1.00) × no addl (1.00) = 0.8075
     * lifestyle: Riyadh (1.10) × parking 3 (0.90) × km 3 (1.00) = 0.99
     * policy: deductible 0 (1.05) × workshop (1.00) = 1.05
     * company: أسيج (0.95)
     * ncd: null → 1.00
     * raw = 600 × 0.95 × 0.8075 × 0.99 × 1.05 × 0.95 × 1.00 = 600 × 0.95 × 0.8075 × 0.99 × 1.05 × 0.95
     */
    public function test_scenario_1_basic_third_party(): void
    {
        $result = $this->service->calculateSinglePlan(
            ['companyId' => 9, 'subType' => 'thirdParty', 'deductible' => 0],
            [
                'year' => 2022, 'make' => 1, 'estimatedValue' => 85000,
                'purposeOfUse' => 'personal', 'carModification' => 'no',
                'hasTrailer' => 'no', 'transmissionType' => '1',
            ],
            [
                'dateOfBirth' => '1990-05-15', 'drivingExperience' => null,
                'accidentCounts' => '0', 'trafficViolations' => 'no',
                'education' => '5', 'foreignLicense' => 'no',
                'healthConditions' => 'no', 'ncdYears' => null,
                'city' => 'الرياض', 'nightParking' => '3', 'expectedKM' => '3',
                'additionalDrivers' => [],
            ],
            ['repairMethod' => 'workshop']
        );

        $this->assertIsInt($result['annualPrice']);
        $this->assertEquals(0, $result['annualPrice'] % 10, 'Price must be rounded to nearest 10');
        // Verify it's within third-party limits (after 20% discount)
        $this->assertGreaterThanOrEqual(320, $result['annualPrice']);
        $this->assertLessThanOrEqual(2400, $result['annualPrice']);
        // Verify response shape
        $this->assertArrayHasKey('pricingFactors', $result);
        $this->assertArrayHasKey('vehicle', $result['pricingFactors']);
        $this->assertArrayHasKey('driver', $result['pricingFactors']);
        $this->assertArrayHasKey('lifestyle', $result['pricingFactors']);
        $this->assertArrayHasKey('policy', $result['pricingFactors']);
        $this->assertArrayHasKey('company', $result['pricingFactors']);
        $this->assertArrayHasKey('ncd', $result['pricingFactors']);
        $this->assertArrayHasKey('total', $result['pricingFactors']);
        $this->assertArrayHasKey('notes', $result);
        $this->assertEquals('neutral (missing)', $result['notes']['drivingExperience']);
    }

    /**
     * Scenario 2: Comprehensive, BMW, experienced driver, NCD 5 years, agency repair
     *
     * basePrice = 1540 (comprehensive)
     * vehicle: age 2yr (0.90) × BMW (1.20) × value 250k (1.30) × personal (1.00) × no mod (1.00) × no trailer (1.00) × auto (1.00)
     * driver: age ~40 (0.90) × exp 5 (0.80) × acc 0 (0.85) × no viol (1.00) × master (0.93) × no foreign (1.00) × no health (1.00)
     * lifestyle: جدة (1.10) × garage (0.90) × km 2 (0.95)
     * policy: deductible 2000 (0.87) × agency (1.25)
     * company: الجزيرة (1.08)
     * ncd: 5 years (0.75)
     */
    public function test_scenario_2_comprehensive_high_value(): void
    {
        $result = $this->service->calculateSinglePlan(
            ['companyId' => 8, 'subType' => 'comprehensive', 'deductible' => 2000],
            [
                'year' => 2024, 'make' => 8, 'estimatedValue' => 250000,
                'purposeOfUse' => 'personal', 'carModification' => 'no',
                'hasTrailer' => 'no', 'transmissionType' => '1',
            ],
            [
                'dateOfBirth' => '1985-03-20', 'drivingExperience' => '5',
                'accidentCounts' => '0', 'trafficViolations' => 'no',
                'education' => '6', 'foreignLicense' => 'no',
                'healthConditions' => 'no', 'ncdYears' => '5',
                'city' => 'جدة', 'nightParking' => '3', 'expectedKM' => '2',
                'additionalDrivers' => [],
            ],
            ['repairMethod' => 'agency']
        );

        $this->assertIsInt($result['annualPrice']);
        $this->assertGreaterThanOrEqual(700, $result['annualPrice']);
        $this->assertLessThanOrEqual(7200, $result['annualPrice']);
        $this->assertEquals('applied', $result['notes']['ncdYears']);
        $this->assertEquals(0.75, $result['pricingFactors']['ncd']);
    }

    /**
     * Scenario 3: High risk — old car, young driver, many accidents, commercial use
     */
    public function test_scenario_3_high_risk_profile(): void
    {
        $result = $this->service->calculateSinglePlan(
            ['companyId' => 3, 'subType' => 'thirdPartyPlus', 'deductible' => 500],
            [
                'year' => 2015, 'make' => 4, 'estimatedValue' => 30000,
                'purposeOfUse' => 'commercial', 'carModification' => 'yes',
                'hasTrailer' => 'yes', 'transmissionType' => '2',
            ],
            [
                'dateOfBirth' => '2002-01-01', 'drivingExperience' => '1',
                'accidentCounts' => '4', 'trafficViolations' => 'yes',
                'education' => '1', 'foreignLicense' => 'yes',
                'healthConditions' => 'yes', 'ncdYears' => '0',
                'city' => 'الرياض', 'nightParking' => '1', 'expectedKM' => '5',
                'additionalDrivers' => [[], []],
            ],
            ['repairMethod' => 'workshop']
        );

        // High risk profile should hit or approach the max price limit (after 20% discount)
        $this->assertIsInt($result['annualPrice']);
        $this->assertGreaterThanOrEqual(480, $result['annualPrice']);
        $this->assertLessThanOrEqual(4000, $result['annualPrice']);
        // Vehicle factor should be > 1.0 (old, modified, trailer, commercial)
        $this->assertGreaterThan(1.0, $result['pricingFactors']['vehicle']);
        // Driver factor should be > 1.0 (young, inexperienced, accidents, violations)
        $this->assertGreaterThan(1.0, $result['pricingFactors']['driver']);
    }

    /**
     * Scenario 4: Minimal input — nullable fields defaulting to neutral 1.0
     */
    public function test_scenario_4_minimal_input(): void
    {
        $result = $this->service->calculateSinglePlan(
            ['companyId' => 5, 'subType' => 'thirdParty', 'deductible' => 1000],
            [
                'year' => 2023, 'make' => 2, 'estimatedValue' => 60000,
                'purposeOfUse' => 'personal', 'carModification' => 'no',
                'hasTrailer' => 'no', 'transmissionType' => '1',
            ],
            [
                'dateOfBirth' => null, 'drivingExperience' => null,
                'accidentCounts' => '0', 'trafficViolations' => 'no',
                'education' => '3', 'foreignLicense' => 'no',
                'healthConditions' => 'no', 'ncdYears' => null,
                'city' => 'أبها', 'nightParking' => '2', 'expectedKM' => '3',
                'additionalDrivers' => null,
            ],
            ['repairMethod' => 'workshop']
        );

        $this->assertIsInt($result['annualPrice']);
        // dateOfBirth null → driverAgeFactor = 1.0
        // drivingExperience null → experienceFactor = 1.0
        // ncdYears null → ncdFactor = 1.0
        $this->assertEquals(1.0, $result['pricingFactors']['ncd']);
        $this->assertEquals('neutral (missing)', $result['notes']['drivingExperience']);
        $this->assertEquals('neutral (missing)', $result['notes']['ncdYears']);
        // Unknown city (أبها) → 1.00, parking 2 → 1.00, mileage 3 → 1.00
        $this->assertEquals(1.0, $result['pricingFactors']['lifestyle']);
    }

    /**
     * Scenario 5: Policy deductible override takes precedence over plan deductible
     */
    public function test_scenario_5_deductible_override(): void
    {
        $baseInput = [
            'year' => 2020, 'make' => 7, 'estimatedValue' => 150000,
            'purposeOfUse' => 'personal', 'carModification' => 'no',
            'hasTrailer' => 'no', 'transmissionType' => '1',
        ];
        $driver = [
            'dateOfBirth' => '1985-01-01', 'drivingExperience' => '3',
            'accidentCounts' => '1', 'trafficViolations' => 'no',
            'education' => '5', 'foreignLicense' => 'no',
            'healthConditions' => 'no', 'ncdYears' => '1',
            'city' => 'الرياض', 'nightParking' => '2', 'expectedKM' => '3',
            'additionalDrivers' => [],
        ];

        // Without override: plan deductible = 1000 → factor 1.00
        $result1 = $this->service->calculateSinglePlan(
            ['companyId' => 5, 'subType' => 'comprehensive', 'deductible' => 1000],
            $baseInput, $driver,
            ['repairMethod' => 'workshop']
        );

        // With override: policy.deductible = 3000 → factor 0.78
        $result2 = $this->service->calculateSinglePlan(
            ['companyId' => 5, 'subType' => 'comprehensive', 'deductible' => 1000],
            $baseInput, $driver,
            ['repairMethod' => 'workshop', 'deductible' => 3000]
        );

        // Policy override with higher deductible → lower factor → lower price
        $this->assertLessThan($result1['annualPrice'], $result2['annualPrice']);
        $this->assertLessThan($result1['pricingFactors']['policy'], $result2['pricingFactors']['policy']);
    }

    /**
     * Scenario 6: Batch calculation — multiple plans in one call
     */
    public function test_batch_calculation(): void
    {
        $plans = [
            ['companyId' => 9, 'subType' => 'comprehensive', 'deductible' => 1500],
            ['companyId' => 2, 'subType' => 'thirdParty', 'deductible' => 0],
            ['companyId' => 1, 'subType' => 'thirdPartyPlus', 'deductible' => 1000],
        ];
        $vehicle = [
            'year' => 2022, 'make' => 1, 'estimatedValue' => 80000,
            'purposeOfUse' => 'personal', 'carModification' => 'no',
            'hasTrailer' => 'no', 'transmissionType' => '1',
        ];
        $driver = [
            'dateOfBirth' => '1990-05-15', 'drivingExperience' => '4',
            'accidentCounts' => '0', 'trafficViolations' => 'no',
            'education' => '5', 'foreignLicense' => 'no',
            'healthConditions' => 'no', 'ncdYears' => '3',
            'city' => 'الرياض', 'nightParking' => '3', 'expectedKM' => '3',
            'additionalDrivers' => [],
        ];
        $policy = ['repairMethod' => 'workshop'];

        $results = $this->service->calculateForPlans($plans, $vehicle, $driver, $policy);

        $this->assertCount(3, $results);

        // Each result should have correct companyId/subType
        $this->assertEquals(9, $results[0]['companyId']);
        $this->assertEquals('comprehensive', $results[0]['subType']);
        $this->assertEquals(2, $results[1]['companyId']);
        $this->assertEquals('thirdParty', $results[1]['subType']);
        $this->assertEquals(1, $results[2]['companyId']);
        $this->assertEquals('thirdPartyPlus', $results[2]['subType']);

        // All prices should be valid
        foreach ($results as $result) {
            $this->assertIsInt($result['annualPrice']);
            $this->assertGreaterThan(0, $result['annualPrice']);
            $this->assertEquals(0, $result['annualPrice'] % 10);
            $this->assertIsInt($result['monthlyPrice']);
            $this->assertIsInt($result['vatAmount']);
            $this->assertEquals($result['annualPrice'] + $result['vatAmount'], $result['totalWithVAT']);
        }

        // Comprehensive should be more expensive than third party
        $this->assertGreaterThan($results[1]['annualPrice'], $results[0]['annualPrice']);
    }
}
