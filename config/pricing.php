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
        1  => 1.02,  // التعاونية
        2  => 1.00,  // الراجحي
        3  => 1.05,  // ولاء
        4  => 1.04,  // ميدغلف
        5  => 1.00,  // ملاذ
        6  => 1.00,  // GIG
        7  => 1.03,  // سلامة
        8  => 1.08,  // الجزيرة
        9  => 1.00,  // أسيج
        10 => 1.01,  // أمانة
        11 => 1.03,  // الدرع العربي
        12 => 1.00,  // الصقر
        13 => 1.06,  // الخليجية العامة
        14 => 1.00,  // بروج
        15 => 1.02,  // إتحاد الخليج
        16 => 1.04,  // متكاملة
        17 => 1.00,  // الإنماء
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

    // ─── Pricing version (for audit trail) ───
    'version' => '1.0.0',

];
