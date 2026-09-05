<?php

namespace Tests\Feature\Api;

use App\Services\GeoLocationService;
use Tests\TestCase;

class GeoCheckTest extends TestCase
{
    /**
     * The geo/check endpoint returns a successful JSON response.
     */
    public function test_geo_check_returns_json_response(): void
    {
        $response = $this->getJson('/api/geo/check');

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'is_saudi',
                     'access_scope',
                     'customer_blocked',
                     'country',
                     'country_code',
                     'country_ar',
                 ]);
    }

    /**
     * The geo/check response has the correct boolean types.
     */
    public function test_geo_check_response_has_correct_types(): void
    {
        $response = $this->getJson('/api/geo/check');

        $response->assertOk();

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertIsBool($data['is_saudi']);
        $this->assertContains($data['access_scope'], ['full', 'local', 'blog']);
    }

    public function test_geo_check_reuses_location_and_disables_shared_caching(): void
    {
        $geo = $this->mock(GeoLocationService::class);
        $location = [
            'country' => 'Saudi Arabia',
            'country_code' => 'SA',
            'region' => 'Riyadh Region',
            'city' => 'Riyadh',
            'lat' => 24.7136,
            'lon' => 46.6753,
        ];

        $geo->shouldReceive('getLocation')->once()->andReturn($location);
        $geo->shouldReceive('isAllowedLocation')->once()->with($location)->andReturnTrue();
        $geo->shouldReceive('isAdminIp')->once()->andReturnFalse();
        $geo->shouldReceive('getArabicCountryName')->once()->with('SA')->andReturn('المملكة العربية السعودية');

        $this->getJson('/api/geo/check')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('is_saudi', true);
    }
}
