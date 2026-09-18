<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for POST /api/quotes/calculate
 */
class QuoteCalculationApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'plans' => [
                ['companyId' => 2, 'subType' => 'comprehensive', 'deductible' => 3000],
                ['companyId' => 1, 'subType' => 'thirdParty', 'deductible' => 1000],
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
                            'vehicle', 'driver', 'lifestyle', 'policy', 'company', 'ncd',
                        ],
                        'notes',
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['quotes']);

        // First quote: comprehensive — fixed price, no dynamic factors
        $this->assertEquals(2, $data['quotes'][0]['companyId']);
        $this->assertEquals('comprehensive', $data['quotes'][0]['subType']);
        $this->assertEquals(2399, $data['quotes'][0]['annualPrice']);

        // Second quote: third party — fixed price, no dynamic factors
        $this->assertEquals(1, $data['quotes'][1]['companyId']);
        $this->assertEquals('thirdParty', $data['quotes'][1]['subType']);
        $this->assertEquals(499, $data['quotes'][1]['annualPrice']);
    }

    public function test_validation_rejects_missing_plans(): void
    {
        $payload = $this->validPayload();
        unset($payload['plans']);

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plans']);
    }

    public function test_validation_rejects_more_than_fifty_plans(): void
    {
        $payload = $this->validPayload();
        $payload['plans'] = array_fill(0, 51, [
            'companyId' => 2,
            'subType' => 'comprehensive',
            'deductible' => 3000,
        ]);

        $this->postJson('/api/quotes/calculate', $payload)
            ->assertStatus(422)
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
        $this->assertIsString($data['quotes'][0]['notes']);
    }

    public function test_policy_deductible_override(): void
    {
        // Fixed pricing applies deductible increase from fixed table.
        $payload = $this->validPayload();
        $payload['plans'] = [
            ['companyId' => 5, 'subType' => 'comprehensive', 'deductible' => 1000],
        ];

        $response1 = $this->postJson('/api/quotes/calculate', $payload);
        $price1 = $response1->json('quotes.0.annualPrice');

        $payload['policy']['deductible'] = 5000;
        $response2 = $this->postJson('/api/quotes/calculate', $payload);
        $price2 = $response2->json('quotes.0.annualPrice');

        $this->assertEquals(999, $price1);
        $this->assertEquals(1249, $price2);
        $this->assertGreaterThan($price1, $price2);
    }

    public function test_single_plan_request(): void
    {
        $payload = $this->validPayload();
        $payload['plans'] = [
            ['companyId' => 1, 'subType' => 'thirdParty', 'deductible' => 1000],
        ];

        $response = $this->postJson('/api/quotes/calculate', $payload);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(1, $data['quotes']);
    }

    public function test_vehicle_value_does_not_change_price(): void
    {
        $payload = $this->validPayload();
        $payload['plans'] = [
            ['companyId' => 1, 'subType' => 'thirdParty', 'deductible' => 3000],
        ];

        $payload['vehicle']['estimatedValue'] = 30000;
        $priceA = $this->postJson('/api/quotes/calculate', $payload)->json('quotes.0.annualPrice');

        $payload['vehicle']['estimatedValue'] = 500000;
        $priceB = $this->postJson('/api/quotes/calculate', $payload)->json('quotes.0.annualPrice');

        $this->assertEquals(649, $priceA);
        $this->assertEquals($priceA, $priceB);
    }
}
