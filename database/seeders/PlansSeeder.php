<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Seed the plans table with fixed prices.
     * Each company × sub-type combination has a fixed annual price.
     */
    public function run(): void
    {
        $subTypes = ['thirdParty', 'comprehensive'];
        $basePrices = [
            'thirdParty'    => 399,
            'comprehensive' => 499,
        ];

        // Create plans for companies 1-21
        for ($companyId = 1; $companyId <= 21; $companyId++) {
            foreach ($subTypes as $subType) {
                Plan::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'sub_type'   => $subType,
                    ],
                    [
                        'base_price' => $basePrices[$subType],
                    ]
                );
            }
        }
    }
}
