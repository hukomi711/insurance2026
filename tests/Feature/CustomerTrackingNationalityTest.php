<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTrackingNationalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_a_resident_persists_their_nationality_for_the_dashboard(): void
    {
        $this->postJson('/api/customer/track', [
            'national_id' => '2000000006',
            'nationality' => 'EG',
            'sequence_number' => '7001646061',
            'registration_type' => 'sequence',
        ], ['X-Session-Token' => 'nationality-test-session'])
            ->assertOk();

        $customer = CustomerProfile::query()->sole();

        $this->assertSame('EG', $customer->extra_data['nationality']);
    }
}
