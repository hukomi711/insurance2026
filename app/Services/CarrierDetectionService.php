<?php

namespace App\Services;

/**
 * Infers the original Saudi mobile carrier/allocation from a phone number prefix.
 *
 * Important:
 * This does NOT guarantee the subscriber's current carrier because Saudi Arabia
 * supports mobile number portability (MNP). Use only for display, analytics,
 * and light segmentation — never for routing-sensitive decisions (e.g.
 * choosing an SMS/OTP provider).
 *
 * Consolidates the duplicate implementations that existed in
 * AdminCustomerController and CustomerTrackingController.
 */
class CarrierDetectionService
{
    private const STC = 'STC';
    private const MOBILY = 'Mobily';
    private const ZAIN = 'Zain';
    private const UNKNOWN = 'Unknown';

    /**
     * Allocation map by local 3-digit mobile prefix (after normalization to
     * the local 9-digit form, e.g. 501234567 → prefix3 = "501").
     *
     * Note: 510–529 are intentionally omitted; they require confirmation
     * against operational data before being mapped to a specific carrier.
     *
     * @var array<int, string>
     */
    private const PREFIX_MAP = [
        // STC
        '500' => self::STC, '501' => self::STC, '502' => self::STC, '503' => self::STC,
        '504' => self::STC, '505' => self::STC, '506' => self::STC, '507' => self::STC,
        '508' => self::STC, '509' => self::STC,
        '530' => self::STC, '531' => self::STC, '532' => self::STC, '533' => self::STC,
        '534' => self::STC, '535' => self::STC,
        '550' => self::STC, '551' => self::STC, '552' => self::STC, '553' => self::STC,
        '554' => self::STC, '555' => self::STC, '556' => self::STC, '557' => self::STC,
        '558' => self::STC, '559' => self::STC,

        // Mobily
        '540' => self::MOBILY, '541' => self::MOBILY, '542' => self::MOBILY, '543' => self::MOBILY,
        '544' => self::MOBILY, '545' => self::MOBILY, '546' => self::MOBILY, '547' => self::MOBILY,
        '548' => self::MOBILY, '549' => self::MOBILY,
        '560' => self::MOBILY, '561' => self::MOBILY, '562' => self::MOBILY, '563' => self::MOBILY,
        '564' => self::MOBILY, '565' => self::MOBILY, '566' => self::MOBILY, '567' => self::MOBILY,
        '568' => self::MOBILY, '569' => self::MOBILY,

        // Zain
        '580' => self::ZAIN, '581' => self::ZAIN, '582' => self::ZAIN, '583' => self::ZAIN,
        '584' => self::ZAIN, '585' => self::ZAIN, '586' => self::ZAIN, '587' => self::ZAIN,
        '588' => self::ZAIN, '589' => self::ZAIN,
        '590' => self::ZAIN, '591' => self::ZAIN, '592' => self::ZAIN, '593' => self::ZAIN,
        '594' => self::ZAIN, '595' => self::ZAIN, '596' => self::ZAIN, '597' => self::ZAIN,
        '598' => self::ZAIN, '599' => self::ZAIN,
    ];

    /**
     * @param  string|null  $phone  Raw phone number (any common format).
     * @return string|null  STC, Mobily, Zain, Unknown, or null for invalid/non-Saudi-mobile input.
     */
    public static function detect(?string $phone): ?string
    {
        $digits = self::normalizeSaudiMobile($phone);

        if ($digits === null) {
            return null;
        }

        $prefix3 = substr($digits, 0, 3);

        return self::PREFIX_MAP[$prefix3] ?? self::UNKNOWN;
    }

    /**
     * Normalize Saudi mobile numbers to the local 9-digit format.
     *
     * Examples:
     *   +966501234567   → 501234567
     *   00966501234567  → 501234567
     *   966501234567    → 501234567
     *   0501234567      → 501234567
     *   501234567       → 501234567
     *
     * Returns null if the number is not a valid Saudi mobile (must be 9
     * digits and start with 5 after normalization).
     */
    public static function normalizeSaudiMobile(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^5\d{8}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}
