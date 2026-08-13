<?php

/**
 * Pricing Engine Configuration
 *
 * Direct 1:1 port of resources/js/data/pricingConstants.js
 * Same keys, same values — enables PHP parity with JS engine.
 *
 * Factor > 1.0 = price increase (higher risk)
 * Factor < 1.0 = discount (lower risk)
 * Factor = 1.0 = neutral
 */

return [

    // ─── Base premiums per insurance sub-type (SAR) ───
    'base_premiums' => [
        'thirdParty'       => 700,
        'thirdPartyPlus'   => 1100,
        'vehicleDamagePlus'=> 1300,
        'comprehensive'    => 2000,
    ],

    // ─── Price limits (min/max per sub-type) ───
    'price_limits' => [
        'thirdParty'        => ['min' => 400,  'max' => 3000],
        'thirdPartyPlus'    => ['min' => 600,  'max' => 5000],
        'vehicleDamagePlus' => ['min' => 700,  'max' => 6000],
        'comprehensive'     => ['min' => 900,  'max' => 9000],
    ],

    // ─── Vehicle risk factors ───

    'vehicle_age_factors' => [
        ['maxAge' => 1,  'factor' => 0.90],  // New (0-1 year) — discount
        ['maxAge' => 3,  'factor' => 0.95],  // Recent (2-3 years)
        ['maxAge' => 5,  'factor' => 1.00],  // Medium (4-5 years)
        ['maxAge' => 8,  'factor' => 1.10],  // Somewhat old (6-8 years)
        ['maxAge' => 12, 'factor' => 1.25],  // Old (9-12 years)
        ['maxAge' => 99, 'factor' => 1.40],  // Very old (13+ years)
    ],


    'vehicle_value_factors' => [
        ['maxValue' => 15000,         'factor' => 0.60],  // Very economy — big discount
        ['maxValue' => 30000,         'factor' => 0.72],  // Economy
        ['maxValue' => 50000,         'factor' => 0.85],  // Low-mid
        ['maxValue' => 80000,         'factor' => 1.00],  // Mid — baseline
        ['maxValue' => 120000,        'factor' => 1.18],  // Above mid
        ['maxValue' => 180000,        'factor' => 1.35],  // Luxury
        ['maxValue' => 250000,        'factor' => 1.55],  // High luxury
        ['maxValue' => PHP_FLOAT_MAX, 'factor' => 1.80],  // Super luxury
    ],

    'purpose_factors' => [
        'personal'   => 1.00,
        'commercial' => 1.25,
        'rental'     => 1.35,
        'rideshare'  => 1.40,
        'cargo'      => 1.30,
        'petroleum'  => 1.45,
    ],

    'modification_factor' => 1.15,
    'trailer_factor'      => 1.10,


    // ─── Driver risk factors ───

    'driver_age_factors' => [
        ['maxAge' => 25, 'factor' => 1.35],  // Young — high risk
        ['maxAge' => 35, 'factor' => 1.00],  // Medium
        ['maxAge' => 50, 'factor' => 1.00],  // Mature — experienced
        ['maxAge' => 65, 'factor' => 1.00],  // Senior
        ['maxAge' => 99, 'factor' => 1.10],  // Elderly
    ],


    'accident_factors' => [
        '0' => 1.00,  // No accidents — neutral
        '1' => 1.00,  // 1 accident
        '2' => 1.20,  // 2 accidents
        '3' => 1.45,  // 3 accidents
        '4' => 1.70,  // 4 accidents
        '5' => 1.90,  // 5+ accidents
    ],

    'violation_factors' => [
        'yes' => 1.15,
        'no'  => 1.00,
    ],

    'education_factors' => [
        '1' => 1.05,  // Primary
        '2' => 1.03,  // Intermediate
        '3' => 1.00,  // Secondary
        '4' => 1.00,  // Diploma
        '5' => 1.00,  // Bachelor
        '6' => 1.00,  // Master
        '7' => 1.00,  // PhD
    ],

    'foreign_license_factor'      => 1.08,
    'health_condition_factor'     => 1.10,
    'additional_driver_factor'    => 1.05,

    // ─── Lifestyle / location factors ───

    'city_factors' => [
        'الرياض'          => 1.15,
        'جدة'             => 1.12,
        'مكة المكرمة'     => 1.08,
        'المدينة المنورة' => 1.03,
        'الدمام'          => 1.08,
        'الخبر'           => 1.06,
        'الظهران'         => 1.04,
        '_default'         => 1.00,
    ],

    'parking_factors' => [
        '1' => 1.12,  // Street — higher risk
        '2' => 1.00,  // Driveway
        '3' => 0.93,  // Garage — discount
    ],

    'mileage_factors' => [
        '1' => 0.90,  // < 5,000 km — low usage discount
        '2' => 0.95,  // 5,000 - 10,000
        '3' => 1.00,  // 10,000 - 20,000 — baseline
        '4' => 1.08,  // 20,000 - 30,000
        '5' => 1.18,  // > 30,000 — higher risk
    ],

    // ─── Policy factors ───

    'deductible_factors' => [
        0    => 1.15,  // No deductible — most expensive
        500  => 1.08,  // Low deductible
        1000 => 1.00,  // Baseline
        1500 => 0.95,  // Slight discount
        2000 => 0.90,  // Good discount
        2500 => 0.85,  // Great discount
        3000 => 0.80,  // Big discount
        5000 => 0.72,  // Maximum discount
    ],

    'repair_method_factors' => [
        'workshop'   => 1.00,
        'authorized' => 1.00,  // Alias for workshop
        'agency'     => 1.35,  // 35% premium for agency repair
    ],

    // ─── Coverage limit factors (comprehensive, vehicleDamagePlus, thirdPartyPlus) ───

    'coverage_limit_factors' => [
        ['maxValue' => 30000,          'factor' => 0.90],
        ['maxValue' => 50000,          'factor' => 0.95],
        ['maxValue' => 80000,          'factor' => 1.00],
        ['maxValue' => 120000,         'factor' => 1.05],
        ['maxValue' => 200000,         'factor' => 1.12],
        ['maxValue' => PHP_FLOAT_MAX,  'factor' => 1.20],
    ],

    // ─── Company pricing factors ───

    'company_pricing_factors' => [
        1  => 1.02,  // تري
        2  => 1.00,  // العربية
        3  => 1.05,  // ولاء
        4  => 1.04,  // ميدغلف
        5  => 1.00,  // ملاذ
        6  => 1.00,  // سايكو
        7  => 1.03,  // سلامة
        8  => 1.08,  // العناية السعودية
        9  => 1.00,  // أسيج
        10 => 1.01,  // أمانة
        11 => 1.03,  // الدرع العربي
        12 => 1.00,  // الصقر
        13 => 1.06,  // التعاونية
        14 => 1.00,  // اتحاد الخليج
        15 => 1.02,  // المتحدة
        16 => 1.03,  // الراجحي
        17 => 1.02,  // الوطنية
        18 => 1.00,  // الخليج العامة
        19 => 1.04,  // GIG
        20 => 1.03,  // الإنماء طوكيو مارين
        21 => 1.01,  // ليفا
    ],

    // ─── Company × insurance subtype pricing factors ───
    // Wider spread than the generic company factor so offers do not collapse
    // into near-identical prices inside one quote category.
    'subtype_company_pricing_factors' => [
        'thirdParty' => [
            1 => 1.18, 2 => 0.94, 3 => 1.11, 4 => 1.08, 5 => 0.97,
            6 => 0.92, 7 => 1.14, 8 => 1.24, 9 => 0.86, 10 => 0.99,
            11 => 1.07, 12 => 0.90, 13 => 1.22, 14 => 0.95, 15 => 1.03,
            16 => 1.16, 17 => 1.05, 18 => 0.93, 19 => 1.12, 20 => 1.09,
            21 => 0.98,
        ],
        'thirdPartyPlus' => [
            1 => 1.08, 2 => 0.96, 3 => 1.10, 4 => 1.04, 5 => 0.94,
            6 => 0.98, 7 => 1.06, 8 => 1.15, 9 => 0.92, 10 => 0.98,
            11 => 1.03, 12 => 0.95, 13 => 1.18, 14 => 0.97, 15 => 1.01,
            16 => 1.14, 17 => 1.04, 18 => 0.96, 19 => 1.07, 20 => 1.02,
            21 => 0.99,
        ],
        'vehicleDamagePlus' => [
            1 => 1.06, 2 => 0.97, 3 => 1.02, 4 => 0.98, 5 => 0.95,
            6 => 1.00, 7 => 1.04, 8 => 1.10, 9 => 0.94, 10 => 0.99,
            11 => 1.05, 12 => 0.93, 13 => 1.22, 14 => 0.96, 15 => 1.02,
            16 => 1.12, 17 => 1.03, 18 => 0.98, 19 => 1.08, 20 => 1.06,
            21 => 0.97,
        ],
        'comprehensive' => [
            1 => 1.10, 2 => 0.97, 3 => 1.12, 4 => 1.24, 5 => 0.95,
            6 => 1.00, 7 => 1.06, 8 => 1.17, 9 => 0.93, 10 => 1.02,
            11 => 1.08, 12 => 0.96, 13 => 1.28, 14 => 0.98, 15 => 1.04,
            16 => 1.18, 17 => 1.03, 18 => 0.99, 19 => 1.21, 20 => 1.08,
            21 => 0.94,
        ],
    ],

    // ─── No Claims Discount (NCD) factors ───

    'ncd_factors' => [
        '0' => 1.00,  // No discount
        '1' => 0.97,  // 1 year — 3% discount
        '2' => 0.93,  // 2 years — 7% discount
        '3' => 0.88,  // 3 years — 12% discount
        '4' => 0.82,  // 4 years — 18% discount
        '5' => 0.75,  // 5 years — 25% discount
        '6' => 0.72,  // 6 years — 28% discount
        '7' => 0.70,  // 7+ years — 30% max discount
    ],

    // ─── VAT rate ───
    'vat_rate' => 0.15,

    // ─── NEW: Manufacturer/Brand factors (vehicle make) ───
    'manufacturer_factors' => [
        // This can be populated with actual brand data
        // Examples: Toyota=1.0, BMW=1.05, Hyundai=0.95, etc.
        // For now, defaults to 1.0 if not specified
    ],

    // ─── NEW: Driving experience factors (years) ───
    'experience_factors' => [
        '1' => 1.10,   // 1 year — high risk
        '2' => 1.05,   // 2 years
        '3' => 1.03,   // 3 years
        '4' => 1.01,   // 4 years
        '5' => 1.00,   // 5+ years — baseline
    ],

    // ─── NEW: Transmission type factors ───
    'transmission_factors' => [
        '1' => 1.00,   // Manual
        '2' => 1.00,   // Automatic (neutral for now)
        '3' => 1.02,   // CVT (slightly higher)
    ],

    // ─── NEW: Digital signature configuration ───
    // Dedicated HMAC key for pricing signatures. Falls back to APP_KEY
    // when empty (backward compatible). Set PRICING_SIGNATURE_KEY in .env
    // to a long random secret to decouple from APP_KEY rotation.
    'signature_key' => env('PRICING_SIGNATURE_KEY', ''),
    'signature_ttl' => (int) env('PRICING_SIGNATURE_TTL', 3600),  // Signature valid for 1 hour (3600 seconds)

    // ─── Promotional discount applied AFTER risk-factor pricing, BEFORE VAT ───
    // Keep this synchronized with PROMOTIONAL_DISCOUNT_FACTOR in pricingConstants.js.
    // Cross-runtime parity tests fail if either side changes independently.
    'promotional_discount_factor' => 0.80,

    // ─── Pricing version (for audit trail and sync) ───
    'version' => '1.1.0',
    'last_updated' => '2026-05-09',

];
