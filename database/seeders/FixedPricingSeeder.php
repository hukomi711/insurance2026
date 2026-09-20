<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixedPricingSeeder extends Seeder
{
    /**
     * Fixed pricing: 13 companies × 4 sub-types
     *
     * Each company has one fixed base price that applies to all sub-types.
     * The pricing is independent of vehicle value, driver profile, or any other factor.
     */
    public function run(): void
    {
        // Fixed base price per company (SAR, without VAT)
        $fixedPrices = [
            1  => 399,      // تري للتأمين
            5  => 749,      // ملاذ للتأمين
            8  => 999,      // العناية السعودية للتأمين
            6  => 1249,     // سايكو للتأمين
            13 => 1499,     // التعاونية للتأمين
            16 => 1749,     // الراجحي للتأمين
            2  => 1999,     // AICC (العربية للتأمين التعاوني)
            19 => 2249,     // GIG للتأمين
            20 => 2499,     // الإنماء طوكيو مارين
            3  => 2749,     // ولاء للتأمين
            4  => 2999,     // ميدغلف للتأمين
            21 => 3249,     // ليفا للتأمين
            17 => 3499,     // الوطنية للتأمين
        ];

        // All available sub-types
        $subTypes = ['thirdParty', 'thirdPartyPlus', 'vehicleDamagePlus', 'comprehensive'];

        // Clear existing plans
        DB::table('plans')->truncate();

        // Create plan records for each company × sub-type combination
        $plans = [];
        foreach ($fixedPrices as $companyId => $basePrice) {
            foreach ($subTypes as $subType) {
                $plans[] = [
                    'company_id' => $companyId,
                    'sub_type'   => $subType,
                    'base_price' => $basePrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($plans, 100) as $chunk) {
            DB::table('plans')->insert($chunk);
        }

        $this->command->info("✅ Populated plans table with fixed pricing:");
        $this->command->info("   • 13 companies");
        $this->command->info("   • 4 sub-types each");
        $this->command->info("   • 52 total plan records");
        $this->command->info("   • Prices range: 499 SAR → 3,499 SAR");
        $this->command->info("   • Pricing is independent of vehicle value ✓");
    }
}
