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
        $fixedCompanyPrices = [
            1  => 499,
            2  => 1999,
            3  => 2749,
            4  => 2999,
            5  => 749,
            6  => 1249,
            8  => 999,
            13 => 1499,
            16 => 1749,
            17 => 3499,
            19 => 2249,
            20 => 2499,
            21 => 3249,
        ];

        // Seed only the fixed-pricing companies in the current contract.
        foreach ($fixedCompanyPrices as $companyId => $basePrice) {
            foreach ($subTypes as $subType) {
                Plan::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'sub_type'   => $subType,
                    ],
                    [
                        'base_price' => $basePrice,
                    ]
                );
            }
        }
    }
}
