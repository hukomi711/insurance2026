<?php

/**
 * Saudi Bank BIN Definitions — single source of truth.
 *
 * Used by:
 *  - AdminPaymentCardController::binLookup()
 *  - Any future BIN-related logic
 *
 * Keys are bank codes used for logo resolution on the frontend.
 * Each entry contains: name, name_ar, and 6-digit BIN prefixes.
 *
 * Verified via bincheck.io April 2026.
 */

return [
    'rajhi' => [
        'name'     => 'Al Rajhi Bank',
        'name_ar'  => 'مصرف الراجحي',
        'prefixes' => [
            '458618', '468564', '468565', '521076', '553680', '588845',
            '440647', '427010', '427011', '457997', '458456', '486654',
            '412943', '432415', '453201', '453286', '434688', '532580',
            '414627', '445827',
            // verified Al Rajhi per bincheck.io (moved from ahli/anb/saib)
            '409201', '462220', '455708', '403024', '410621',
            // 4-digit short prefix (mada co-branded)
            '4847',
        ],
    ],
    'ahli' => [
        'name'     => 'SNB (Al Ahli)',
        'name_ar'  => 'البنك الأهلي',
        'prefixes' => [
            '489536', '431361', '439954', '490032', '410820',
            '422820', '422821',
            // 4-digit short prefix (SNB / Al Ahli)
            '5294',
        ],
    ],
    'inma' => [
        'name'     => 'Alinma Bank',
        'name_ar'  => 'مصرف الإنماء',
        'prefixes' => [
            '485824', '485825', '485823', '968205',
            '485826', '485827',
            // verified Alinma per bincheck.io (moved from ahli/jazira/bilad)
            '543357', '432328', '428671', '412565', '407197',
            // 4-digit short prefix
            '4323',
        ],
    ],
    'sabb' => [
        'name'     => 'SABB',
        'name_ar'  => 'بنك ساب',
        'prefixes' => [
            '401757', '410685', '420132', '431313', '474491',
            '423854', '447264',
            // 4-digit short prefixes (SAB / Al Awwal)
            '4228', '4272',
        ],
    ],
    'jazira' => [
        'name'     => 'Bank AlJazira',
        'name_ar'  => 'بنك الجزيرة',
        'prefixes' => [
            '423766', '483510',
            // 4-digit short prefix
            '4405',
        ],
    ],
    'riyad' => [
        'name'     => 'Riyad Bank',
        'name_ar'  => 'بنك الرياض',
        'prefixes' => [
            '417634', '421141', '422817', '439357',
            '489318', '420651', '428331',
            // verified Riyad per bincheck.io (moved from rajhi)
            '527016',
            // 4-digit short prefix
            '5297',
        ],
    ],
    'bilad' => [
        'name'     => 'Bank AlBilad',
        'name_ar'  => 'بنك البلاد',
        'prefixes' => [
            '402962', '432237', '403888',
            // verified Bilad per bincheck.io (moved from jazira/inma/riyad)
            '468540', '468541', '636120', '417633',
        ],
    ],
    'anb' => [
        'name'     => 'Arab National Bank',
        'name_ar'  => 'البنك العربي الوطني',
        'prefixes' => [
            '431062', '406136', '419593', '432156',
            // verified ANB per bincheck.io (moved from ahli/rajhi)
            '486094', '455036', '524940',
            // 4-digit short prefixes
            '4550', '4860',
        ],
    ],
    'saib' => [
        'name'     => 'Saudi Investment Bank',
        'name_ar'  => 'البنك السعودي للاستثمار',
        'prefixes' => [
            '420259', '450290',
            // 4-digit short prefix
            '4830',
        ],
    ],
    'bsf' => [
        'name'     => 'Banque Saudi Fransi',
        'name_ar'  => 'البنك السعودي الفرنسي',
        'prefixes' => [
            '440795', '446404', '457865', '403941', '406996', '489317',
            // 4-digit short prefix
            '4406',
        ],
    ],
    'gib' => [
        'name'     => 'Gulf International Bank',
        'name_ar'  => 'بنك الخليج الدولي',
        'prefixes' => [
            '403635', '404610', '417564', '468544',
        ],
    ],
    'stc' => [
        'name'     => 'STC Bank',
        'name_ar'  => 'بنك stc',
        'prefixes' => [
            '4201',
        ],
    ],
    'enbd' => [
        'name'     => 'Emirates NBD',
        'name_ar'  => 'الإمارات دبي الوطني',
        'prefixes' => [
            '4106',
        ],
    ],
    'barraq' => [
        'name'     => 'Barraq Finance',
        'name_ar'  => 'براق للتمويل',
        'prefixes' => [
            '4548',
        ],
    ],

    // ── Mada-specific BIN prefixes (not bank-specific) ──────────────
    '_mada_bins' => [
        '446404', '440795', '440647', '421141', '474491', '588845',
        '968208', '457997', '457865', '468540', '468541', '468542',
        '468543', '417633', '446393', '636120', '968201', '446672',
        '558848', '457144',
        // 4-digit mada co-branded
        '4847',
    ],
];
