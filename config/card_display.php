<?php

/**
 * Card Display Configuration
 *
 * Controls whether payment card data is displayed masked or unmasked
 * in the admin dashboard and export reports.
 *
 * SECURITY WARNING:
 * - Setting 'unmasked' mode to true exposes full PAN, expiry, CVV
 * - Only enable for authorized admin users in secure environments
 * - Always log access to sensitive card data for audit trails
 * - Ensure encryption, HTTPS, and access controls are in place
 */

return [
    /**
     * Default display mode for admin views
     *
     * Values:
     *   'masked'    - Shows last 4 only: •••• •••• •••• 1234
     *   'unmasked'  - Shows full card number: 4111 1111 1111 1111
     *   'partial'   - Shows first 6 + last 4: 411111•• •••• 1111
     *
     * Recommended: 'masked' for production
     */
    'mode' => env('CARD_DISPLAY_MODE', 'masked'),

    /**
     * Show CVV/Security Code in admin views
     *
     * CRITICAL SECURITY RISK:
     * - Never display CVV in screenshots or PDFs
     * - CVV should only appear in real-time admin dashboard
     * - Never log or store CVV displays in audit trails
     * - Keep false in production unless absolutely required
     */
    'show_cvv' => env('CARD_DISPLAY_CVV', false),

    /**
     * Show full expiry date in admin views
     *
     * Values:
     *   'hidden'    - Hide: MM/YY (not shown)
     *   'masked'    - Show month only: MM/**
     *   'visible'   - Show full: MM/YY (default)
     */
    'show_expiry' => env('CARD_DISPLAY_EXPIRY', 'visible'),

    /**
     * Encryption key for reversible masking
     *
     * Leave empty to use default app key.
     * Used for temporary reversible masking of PAN.
     */
    'masking_key' => env('CARD_MASKING_KEY', null),

    /**
     * Audit logging settings
     */
    'audit' => [
        /**
         * Log all access to unmasked card data
         */
        'log_unmasked_access' => env('CARD_AUDIT_LOG_UNMASKED', true),

        /**
         * Require authentication for unmasked display
         */
        'require_auth' => env('CARD_REQUIRE_AUTH_FOR_UNMASKED', true),

        /**
         * Require specific permission/role for unmasked display
         */
        'require_permission' => env('CARD_REQUIRE_PERMISSION', 'admin'),

        /**
         * Maximum number of cards displayable unmasked per day per user
         */
        'daily_limit' => env('CARD_DISPLAY_DAILY_LIMIT', null),
    ],

    /**
     * Display formats for different contexts
     */
    'formats' => [
        /**
         * Dashboard admin view
         */
        'dashboard' => [
            'mode' => 'partial',
            'show_cvv' => false,
            'show_expiry' => 'visible',
        ],

        /**
         * Export reports (PDF/HTML)
         */
        'export' => [
            'mode' => 'masked',
            'show_cvv' => false,
            'show_expiry' => 'visible',
        ],

        /**
         * Email notifications
         */
        'email' => [
            'mode' => 'masked',
            'show_cvv' => false,
            'show_expiry' => 'visible',
        ],

        /**
         * Customer-facing receipts
         */
        'customer' => [
            'mode' => 'masked',
            'show_cvv' => false,
            'show_expiry' => 'visible',
        ],

        /**
         * Authorized unmasked view (admin only)
         */
        'admin_details' => [
            'mode' => 'unmasked',
            'show_cvv' => false,  // CVV requires separate toggle
            'show_expiry' => 'visible',
        ],
    ],

    /**
     * Card number masking patterns
     *
     * Defines how to mask card numbers for each display mode.
     * {full} = full card number
     * {first6} = first 6 digits (BIN)
     * {last4} = last 4 digits
     * {network} = card network (Visa, MC, Mada, etc)
     */
    'patterns' => [
        /**
         * Fully masked: •••• •••• •••• 1234
         */
        'masked' => '•••• •••• •••• {last4}',

        /**
         * Partial: 411111•• •••• 1111
         */
        'partial' => '{first6}•• •••• {last4}',

        /**
         * Full: 4111 1111 1111 1111
         */
        'unmasked' => '{full}',

        /**
         * Last4 only: 1234
         */
        'last4' => '{last4}',

        /**
         * BIN6 + Last4: 411111 - 1111
         */
        'bin_and_last4' => '{first6} - {last4}',
    ],

    /**
     * Bank/Brand styling for card display
     *
     * Maps bank keys to display properties
     */
    'bank_styling' => [
        'rajhi' => [
            'color' => '#003b71',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'ahli' => [
            'color' => '#0e7c3a',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'inma' => [
            'color' => '#6a1b9a',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'sabb' => [
            'color' => '#c8102e',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'jazira' => [
            'color' => '#a31616',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'riyad' => [
            'color' => '#0c2c5a',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'bilad' => [
            'color' => '#a17b1f',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'anb' => [
            'color' => '#08385a',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'saib' => [
            'color' => '#1f3a5f',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
        'bsf' => [
            'color' => '#0a6240',
            'text_color' => '#ffffff',
            'style' => 'rounded',
        ],
    ],

    /**
     * Card shape mappings based on bank/type
     *
     * Defines the physical card design/shape for each bank
     */
    'card_shapes' => [
        /**
         * ISO/IEC 7810 ID-1: Standard credit card (85.6 × 53.98 mm)
         */
        'standard' => [
            'width' => '360px',
            'height' => '215px',
            'radius' => '14px',
            'aspect_ratio' => '1.667',
        ],

        /**
         * Curved edge variant
         */
        'curved' => [
            'width' => '360px',
            'height' => '215px',
            'radius' => '24px',
            'aspect_ratio' => '1.667',
        ],

        /**
         * Minimal/flat design
         */
        'minimal' => [
            'width' => '360px',
            'height' => '215px',
            'radius' => '8px',
            'aspect_ratio' => '1.667',
        ],

        /**
         * Premium/premium card
         */
        'premium' => [
            'width' => '360px',
            'height' => '215px',
            'radius' => '12px',
            'aspect_ratio' => '1.667',
        ],

        /**
         * Oversized/display variant
         */
        'display' => [
            'width' => '480px',
            'height' => '287px',
            'radius' => '18px',
            'aspect_ratio' => '1.667',
        ],
    ],
];
