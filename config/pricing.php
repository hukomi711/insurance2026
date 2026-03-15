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
        'thirdParty'       => 600,
        'thirdPartyPlus'   => 900,
        'vehicleDamagePlus'=> 980,
        'comprehensive'    => 1540,
    ],

    // ─── Price limits (min/max per sub-type) ───
    'price_limits' => [
        'thirdParty'        => ['min' => 500,  'max' => 2000],
        'thirdPartyPlus'    => ['min' => 750,  'max' => 3000],
        'vehicleDamagePlus' => ['min' => 840,  'max' => 3500],
        'comprehensive'     => ['min' => 1260, 'max' => 5600],
    ],

    // ─── Vehicle risk factors ───

    'vehicle_age_factors' => [
        ['maxAge' => 2,  'factor' => 0.90],  // New (0-2 years)
        ['maxAge' => 5,  'factor' => 1.00],  // Medium (3-5 years)
        ['maxAge' => 8,  'factor' => 1.10],  // Somewhat old (6-8 years)
        ['maxAge' => 99, 'factor' => 1.25],  // Old (9+ years)
    ],

    'manufacturer_factors' => [
        // Japanese — high reliability
        1  => 0.95,  // Toyota
        9  => 0.95,  // Lexus
        3  => 0.97,  // Nissan
        // Korean
        2  => 0.97,  // Hyundai
        6  => 0.97,  // Kia
        // American
        4  => 1.05,  // Chevrolet
        5  => 1.05,  // Ford
        10 => 1.05,  // GMC
        // European — expensive parts
        7  => 1.20,  // Mercedes
        8  => 1.20,  // BMW
    ],

    'vehicle_value_factors' => [
        ['maxValue' => 50000,    'factor' => 0.85],
        ['maxValue' => 100000,   'factor' => 1.00],
        ['maxValue' => 200000,   'factor' => 1.15],
        ['maxValue' => PHP_FLOAT_MAX, 'factor' => 1.30],
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

    'transmission_factors' => [
        '1' => 1.00,  // Automatic
        '2' => 0.97,  // Manual
    ],

    // ─── Driver risk factors ───

    'driver_age_factors' => [
        ['maxAge' => 25, 'factor' => 1.35],  // Young — high risk
        ['maxAge' => 35, 'factor' => 1.00],  // Medium
        ['maxAge' => 50, 'factor' => 0.90],  // Mature — experienced
        ['maxAge' => 65, 'factor' => 0.95],  // Senior
        ['maxAge' => 99, 'factor' => 1.10],  // Elderly
    ],

    'experience_factors' => [
        '1' => 1.40,  // Less than 1 year
        '2' => 1.15,  // 1-3 years
        '3' => 1.00,  // 3-5 years
        '4' => 0.90,  // 5-10 years
        '5' => 0.80,  // 10+ years
    ],

    'accident_factors' => [
        '0' => 0.85,  // No accidents — discount
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
        '4' => 0.97,  // Diploma
        '5' => 0.95,  // Bachelor
        '6' => 0.93,  // Master
        '7' => 0.90,  // PhD
    ],

    'foreign_license_factor'      => 1.08,
    'health_condition_factor'     => 1.10,
    'additional_driver_factor'    => 1.05,

    // ─── Lifestyle / location factors ───

    'city_factors' => [
        'الرياض'          => 1.10,
        'جدة'             => 1.10,
        'مكة المكرمة'     => 1.05,
        'المدينة المنورة' => 1.03,
        'الدمام'          => 1.05,
        'الخبر'           => 1.05,
        'الظهران'         => 1.03,
        '_default'         => 0.95,
    ],

    'parking_factors' => [
        '1' => 1.10,  // Street
        '2' => 1.00,  // Driveway
        '3' => 0.90,  // Garage
    ],

    'mileage_factors' => [
        '1' => 0.85,  // < 5,000 km
        '2' => 0.95,  // 5,000 - 10,000
        '3' => 1.00,  // 10,000 - 20,000
        '4' => 1.10,  // 20,000 - 30,000
        '5' => 1.20,  // > 30,000
    ],

    // ─── Policy factors ───

    'deductible_factors' => [
        0    => 1.05,  // No deductible (third party)
        500  => 1.10,
        1000 => 1.00,
        1500 => 0.93,
        2000 => 0.87,
        2500 => 0.82,
        3000 => 0.78,
        5000 => 0.70,
    ],

    'repair_method_factors' => [
        'workshop'   => 1.00,
        'authorized' => 1.00,  // Alias for workshop
        'agency'     => 1.25,
    ],

    // ─── Company pricing factors ───

    'company_pricing_factors' => [
        1  => 1.02,  // التعاونية
        2  => 0.98,  // الراجحي
        3  => 1.05,  // ولاء
        4  => 1.04,  // ميدغلف
        5  => 1.00,  // ملاذ
        6  => 0.97,  // GIG
        7  => 1.03,  // سلامة
        8  => 1.08,  // الجزيرة
        9  => 0.95,  // أسيج
        10 => 1.01,  // أمانة
        11 => 1.03,  // الدرع العربي
        12 => 1.00,  // الصقر
        13 => 1.06,  // الخليجية العامة
        14 => 0.99,  // بروج
        15 => 1.02,  // إتحاد الخليج
        16 => 1.04,  // متكاملة
        17 => 0.96,  // الإنماء
    ],

    // ─── No Claims Discount (NCD) factors ───

    'ncd_factors' => [
        '0' => 1.00,  // No discount
        '1' => 0.95,  // 1 year — 5% discount
        '2' => 0.90,  // 2 years — 10%
        '3' => 0.85,  // 3 years — 15%
        '4' => 0.80,  // 4 years — 20%
        '5' => 0.75,  // 5 years — 25%
        '6' => 0.70,  // 6 years — 30%
        '7' => 0.65,  // 7+ years — 35%
    ],

    // ─── VAT rate ───
    'vat_rate' => 0.15,

    // ─── Pricing version (for audit trail) ───
    'version' => '1.0.0',

];
