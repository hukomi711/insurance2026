<?php

namespace Tests\Feature\Api;

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
}
