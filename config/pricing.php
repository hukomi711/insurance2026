<?php

/**
 * Fixed pricing configuration (single source of truth).
 *
 * Active formula (pre-VAT):
 * base company price + comprehensive fixed gap (if applicable)
 * + deductible increase + selected addons total
 */

return [
    // VAT
    'vat_rate' => 0.15,

    // Signature/HMAC configuration for quote integrity packets.
    'signature_key' => env('PRICING_SIGNATURE_KEY', ''),
    'signature_ttl' => (int) env('PRICING_SIGNATURE_TTL', 3600),

    // Fixed base prices by company_id (SAR, before VAT).
    'fixed_company_prices' => [
        1  => 499,
        5  => 749,
        8  => 999,
        6  => 1249,
        13 => 1499,
        16 => 1749,
        2  => 1999,
        19 => 2249,
        20 => 2499,
        3  => 2749,
        4  => 2999,
        21 => 3249,
        17 => 3499,
    ],

    // Fixed surcharge for comprehensive plans (SAR).
    'comprehensive_fixed_gap' => 250,

    // Deductible increase amounts (SAR).
    'deductible_increase' => [
        1000 => 0,
        2000 => 100,
        3000 => 150,
        4000 => 200,
        5000 => 250,
    ],

    // Supported addons and prices (SAR).
    'addons_prices' => [
        0 => [
            'name' => 'تغطية الحوادث الشخصية للسائق',
            'price' => 85,
        ],
        1 => [
            'name' => 'تغطية الحوادث الشخصية للراكب',
            'price' => 510,
        ],
    ],

    // Version metadata for client sync and diagnostics.
    'version' => '2.0.0',
    'last_updated' => '2026-09-18',
];
