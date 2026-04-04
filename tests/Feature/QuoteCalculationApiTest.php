<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Integration tests for POST /api/quotes/calculate
 */
class QuoteCalculationApiTest extends TestCase
{
    private function validPayload(): array
    {
        return [
            'plans' => [
                ['companyId' => 9, 'subType' => 'comprehensive', 'deductible' => 1500],
                ['companyId' => 2, 'subType' => 'thirdParty', 'deductible' => 0],
            ],
            'vehicle' => [
                'year' => 2022,
                'make' => 1,
                'estimatedValue' => 85000,
                'purposeOfUse' => 'personal',
                'carModification' => 'no',
                'hasTrailer' => 'no',
                'transmissionType' => '1',
            ],
            'driver' => [
                'dateOfBirth' => '1990-05-15',
                'drivingExperience' => '4',
                'accidentCounts' => '0',
                'trafficViolations' => 'no',
                'education' => '5',
                'foreignLicense' => 'no',
                'healthConditions' => 'no',
                'ncdYears' => '3',
                'city' => 'الرياض',
                'nightParking' => '3',
                'expectedKM' => '3',
                'additionalDrivers' => [],
            ],
            'policy' => [
                'repairMethod' => 'workshop',
            ],
        ];
    }

    public function test_successful_batch_calculation(): void
    {
        $response = $this->postJson('/api/quotes/calculate', $this->validPayload());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'quotes' => [
                    '*' => [
                        'companyId',
                        'subType',
                        'annualPrice',
                        'monthlyPrice',
                        'vatAmount',
                        'totalWithVAT',
                        'basePrice',
                        'pricingFactors' => [
                            'vehicle', 'driver', 'lifestyle', 'policy', 'company', 'ncd', 'total',
                        ],
                        'notes',
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['quotes']);

        // First quote: comprehensive
        $this->assertEquals(9, $data['quotes'][0]['companyId']);
        $this->assertEquals('comprehensive', $data['quotes'][0]['subType']);
        $this->assertGreaterThanOrEqual(1260, $data['quotes'][0]['annualPrice']);
        $this->assertLessThanOrEqual(5600, $data['quotes'][0]['annualPrice']);

        // Second quote: third party
        $this->assertEquals(2, $data['quotes'][1]['companyId']);
        $this->assertEquals('thirdParty', $data['quotes'][1]['subType']);
        $this->assertGreaterThanOrEqual(500, $data['quotes'][1]['annualPrice']);
        $this->assertLessThanOrEqual(2000, $data['quotes'][1]['annualPrice']);
    }

    public function test_validation_rejects_missing_plans(): void
    {
        $payload = $this->validPayload();
        unset($payload['plans']);

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plans']);
    }

    public function test_validation_rejects_invalid_sub_type(): void
    {
        $payload = $this->validPayload();
        $payload['plans'][0]['subType'] = 'invalid';

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plans.0.subType']);
    }

    public function test_validation_rejects_missing_vehicle(): void
    {
        $payload = $this->validPayload();
        unset($payload['vehicle']);

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle']);
    }

    public function test_validation_rejects_invalid_accident_count(): void
    {
        $payload = $this->validPayload();
        $payload['driver']['accidentCounts'] = '99';

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['driver.accidentCounts']);
    }

    public function test_nullable_fields_accepted(): void
    {
        $payload = $this->validPayload();
        $payload['driver']['dateOfBirth'] = null;
        $payload['driver']['drivingExperience'] = null;
        $payload['driver']['ncdYears'] = null;
        $payload['driver']['additionalDrivers'] = null;

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('neutral (missing)', $data['quotes'][0]['notes']['drivingExperience']);
        $this->assertEquals('neutral (missing)', $data['quotes'][0]['notes']['ncdYears']);
    }

    public function test_policy_deductible_override(): void
    {
        $payload = $this->validPayload();
        // Use a high-value vehicle so the price stays above the minimum clamp
        $payload['vehicle']['make'] = 7;             // Mercedes → factor 1.20
        $payload['vehicle']['estimatedValue'] = 150000; // → factor 1.15
        $payload['vehicle']['year'] = 2020;           // age 6 → factor 1.10
        // Only one comprehensive plan
        $payload['plans'] = [
            ['companyId' => 5, 'subType' => 'comprehensive', 'deductible' => 1000],
        ];

        // Without policy override
        $response1 = $this->postJson('/api/quotes/calculate', $payload);
        $price1 = $response1->json('quotes.0.annualPrice');

        // With policy deductible override — higher deductible → lower price
        $payload['policy']['deductible'] = 5000;
        $response2 = $this->postJson('/api/quotes/calculate', $payload);
        $price2 = $response2->json('quotes.0.annualPrice');

        $this->assertLessThan($price1, $price2);
    }

    public function test_single_plan_request(): void
    {
        $payload = $this->validPayload();
        $payload['plans'] = [
            ['companyId' => 1, 'subType' => 'thirdParty', 'deductible' => 0],
        ];

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(1, $data['quotes']);
    }
}
