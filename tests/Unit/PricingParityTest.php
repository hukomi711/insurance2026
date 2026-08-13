<?php

namespace Tests\Unit;

use App\Services\QuoteCalculationService;
use Carbon\Carbon;
use Tests\TestCase;

class PricingParityTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_php_engine_matches_shared_pricing_fixtures(): void
    {
        $fixtures = json_decode(
            file_get_contents(base_path('tests/Fixtures/pricing-parity.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        Carbon::setTestNow($fixtures['referenceTime']);

        $service = app(QuoteCalculationService::class);

        foreach ($fixtures['cases'] as $case) {
            $result = $service->calculateSinglePlan(
                $case['plan'],
                $case['vehicle'],
                $case['driver'],
                $case['policy']
            );

            $context = "Pricing fixture [{$case['name']}]";

            foreach (['annualPrice', 'originalPrice', 'monthlyPrice', 'vatAmount', 'totalWithVAT', 'basePrice'] as $field) {
                $this->assertSame($case['expected'][$field], $result[$field], "{$context}: {$field}");
            }

            foreach ($case['expected']['factors'] as $factor => $expected) {
                $this->assertEqualsWithDelta(
                    (float) $expected,
                    (float) $result['pricingFactors'][$factor],
                    0.000001,
                    "{$context}: factor {$factor}"
                );
            }
        }
    }
}
