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
 */

return [
    'rajhi' => [
        'name'     => 'Al Rajhi Bank',
        'name_ar'  => 'مصرف الراجحي',
        'prefixes' => [
            '458618', '468564', '468565', '521076', '524940', '527016',
            '543357', '553680', '588845', '440647', '427010', '427011',
            '457997', '458456', '486654', '412943', '432415',
            '453201', '453286', '434688', '532580',
        ],
    ],
    'ahli' => [
        'name'     => 'SNB (Al Ahli)',
        'name_ar'  => 'البنك الأهلي',
        'prefixes' => [
            '489536', '409201', '431361', '439954', '432328', '428671',
            '462220', '455708', '486094', '490032', '410820', '455036',
            '422820', '422821',
        ],
    ],
    'inma' => [
        'name'     => 'Alinma Bank',
        'name_ar'  => 'مصرف الإنماء',
        'prefixes' => [
            '485824', '485825', '485823', '636120', '968205',
            '485826', '485827',
        ],
    ],
    'sabb' => [
        'name'     => 'SABB',
        'name_ar'  => 'بنك ساب',
        'prefixes' => [
            '401757', '410685', '420132', '431313', '474491',
            '423854', '447264',
        ],
    ],
    'jazira' => [
        'name'     => 'Bank AlJazira',
        'name_ar'  => 'بنك الجزيرة',
        'prefixes' => [
            '468540', '468541', '412565', '423766', '483510',
        ],
    ],
    'riyad' => [
        'name'     => 'Riyad Bank',
        'name_ar'  => 'بنك الرياض',
        'prefixes' => [
            '417633', '417634', '421141', '422817', '439357',
            '489318', '420651', '428331',
        ],
    ],
    'bilad' => [
        'name'     => 'Bank AlBilad',
        'name_ar'  => 'بنك البلاد',
        'prefixes' => [
            '402962', '432237', '407197', '403888',
        ],
    ],
    'anb' => [
        'name'     => 'Arab National Bank',
        'name_ar'  => 'البنك العربي الوطني',
        'prefixes' => [
            '431062', '403024', '406136', '419593', '432156',
        ],
    ],
    'saib' => [
        'name'     => 'Saudi Investment Bank',
        'name_ar'  => 'البنك السعودي للاستثمار',
        'prefixes' => [
            '410621', '420259', '450290',
        ],
    ],
    'bsf' => [
        'name'     => 'Banque Saudi Fransi',
        'name_ar'  => 'البنك السعودي الفرنسي',
        'prefixes' => [
            '440795', '446404', '457865', '403941', '406996', '489317',
        ],
    ],

    // ── Mada-specific BIN prefixes (not bank-specific) ──────────────
    '_mada_bins' => [
        '446404', '440795', '440647', '421141', '474491', '588845',
        '968208', '457997', '457865', '468540', '468541', '468542',
        '468543', '417633', '446393', '636120', '968201', '446672',
        '558848', '457144',
    ],
];
