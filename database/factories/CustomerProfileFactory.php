<?php

namespace Database\Factories;

use App\Models\CustomerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerProfileFactory extends Factory
{
    protected $model = CustomerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'ip_address' => $this->faker->ipv4(),
            'session_id' => $this->faker->sha1(),
            'full_name' => $this->faker->name('ar_SA'),
            'phone_number' => $this->faker->phoneNumber(),
            'location_country' => 'السعودية',
            'region' => 'الرياض',
            'is_active' => true,
            'total_visits' => 1,
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
