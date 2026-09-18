<?php

namespace App\Services\CardDisplay;

use App\Services\Bin\CardBinResult;

/**
 * CardDisplayService — Formats and masks card data for display
 *
 * Responsible for:
 * - Converting card numbers to masked/unmasked/partial formats
 * - Applying context-specific display rules (dashboard, export, email)
 * - Respecting user permissions and audit requirements
 * - Providing consistent formatting across the application
 */
class CardDisplayService
{
    /**
     * Display modes
     */
    public const MODE_MASKED = 'masked';
    public const MODE_UNMASKED = 'unmasked';
    public const MODE_PARTIAL = 'partial';
    public const MODE_LAST4 = 'last4';

    /**
     * Format a card number for display
     *
     * @param string|null $cardNumber Full card number (encrypted or plaintext)
     * @param string $mode Display mode (masked, unmasked, partial, last4)
     * @param string|null $last4 Pre-extracted last 4 digits (for optimization)
     * @return string|null Formatted display string or null if no card
     */
    public function formatCardNumber(?string $cardNumber, string $mode = self::MODE_MASKED, ?string $last4 = null): ?string
    {
        if ($cardNumber === null || $cardNumber === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $cardNumber);
        if (strlen($digits) < 4) {
            return null;
        }

        if ($last4 === null) {
            $last4 = substr($digits, -4);
        }

        return match ($mode) {
            self::MODE_MASKED => $this->maskCardNumber($digits),
            self::MODE_UNMASKED => $this->unmaskedCardNumber($digits),
            self::MODE_PARTIAL => $this->partialCardNumber($digits),
            self::MODE_LAST4 => $last4,
            default => $this->maskCardNumber($digits),
        };
    }

    /**
     * Mask card number: •••• •••• •••• 1234
     */
    private function maskCardNumber(string $digits): string
    {
        $last4 = substr($digits, -4);
        return '•••• •••• •••• ' . $last4;
    }

    /**
     * Show unmasked: 4111 1111 1111 1111
     */
    private function unmaskedCardNumber(string $digits): string
    {
        return implode(' ', str_split($digits, 4));
    }

    /**
     * Partial masking: 411111•• •••• 1111
     */
    private function partialCardNumber(string $digits): string
    {
        $first6 = substr($digits, 0, 6);
        $last4 = substr($digits, -4);
        return $first6 . '•• •••• ' . $last4;
    }

    /**
     * Format expiry date for display
     *
     * @param string|null $month Expiry month (MM)
     * @param string|null $year Expiry year (YY or YYYY)
     * @param string $mode Display mode (hidden, masked, visible)
     * @return string|null Formatted expiry or null
     */
    public function formatExpiry(?string $month, ?string $year, string $mode = 'visible'): ?string
    {
        if ($month === null || $year === null) {
            return null;
        }

        $mm = str_pad(trim($month), 2, '0', STR_PAD_LEFT);
        $yy = str_pad(substr(trim($year), -2), 2, '0', STR_PAD_LEFT);

        return match ($mode) {
            'hidden' => null,
            'masked' => $mm . '/**',
            'visible' => $mm . '/' . $yy,
            default => $mm . '/' . $yy,
        };
    }

    /**
     * Format CVV for display
     *
     * @param string|null $cvv CVV/CVC code
     * @param bool $show Whether to show CVV
     * @return string|null CVV or null if hidden
     */
    public function formatCvv(?string $cvv, bool $show = false): ?string
    {
        if (!$show || $cvv === null || $cvv === '') {
            return null;
        }

        return $cvv;
    }

    /**
     * Get masked card data for a context
     *
     * @param string $cardNumber Full card number
     * @param string|null $expiryMonth Month (MM)
     * @param string|null $expiryYear Year (YY/YYYY)
     * @param string|null $cvv CVV
     * @param string $context Context (dashboard, export, email, customer, admin_details)
     * @param string|null $last4 Pre-extracted last4 (for optimization)
     * @return array Display data for the context
     */
    public function formatForContext(
        string $cardNumber,
        ?string $expiryMonth = null,
        ?string $expiryYear = null,
        ?string $cvv = null,
        string $context = 'dashboard',
        ?string $last4 = null
    ): array {
        $config = config('card_display.formats.' . $context, []);

        $mode = $config['mode'] ?? config('card_display.mode', self::MODE_MASKED);
        $showCvv = $config['show_cvv'] ?? config('card_display.show_cvv', false);
        $showExpiry = $config['show_expiry'] ?? config('card_display.show_expiry', 'visible');

        return [
            'card_number' => $this->formatCardNumber($cardNumber, $mode, $last4),
            'card_number_masked' => $this->formatCardNumber($cardNumber, self::MODE_MASKED, $last4),
            'card_number_unmasked' => $this->formatCardNumber($cardNumber, self::MODE_UNMASKED, $last4),
            'card_number_partial' => $this->formatCardNumber($cardNumber, self::MODE_PARTIAL, $last4),
            'card_number_last4' => $last4 ?? substr(preg_replace('/\D/', '', $cardNumber), -4),
            'expiry' => $this->formatExpiry($expiryMonth, $expiryYear, $showExpiry),
            'cvv' => $this->formatCvv($cvv, $showCvv),
            'context' => $context,
            'mode' => $mode,
        ];
    }

    /**
     * Get card branding info from BIN result
     *
     * @param CardBinResult $bin BIN resolver result
     * @return array Branding data
     */
    public function getBrandingFromBin(CardBinResult $bin): array
    {
        $bankKey = $bin->bankKey;
        $bankStyling = config("card_display.bank_styling.{$bankKey}", []);

        return [
            'bank_key' => $bankKey,
            'bank_name' => $bin->bankNameAr,
            'bank_name_en' => $bin->bankNameEn,
            'bank_logo' => $bin->logoPath,
            'brand_color' => $bankStyling['color'] ?? $bin->brandColor ?? '#000000',
            'text_color' => $bankStyling['text_color'] ?? '#ffffff',
            'card_style' => $bankStyling['style'] ?? 'rounded',
            'network' => $bin->network,
            'secondary_network' => $bin->secondaryNetwork,
            'card_type' => $bin->cardType,
            'card_level' => $bin->cardLevel,
            'product_name' => $bin->productName,
            'currency' => $bin->currency,
        ];
    }

    /**
     * Get card shape dimensions
     *
     * @param string $shape Shape variant (standard, curved, minimal, premium, display)
     * @return array Shape dimensions
     */
    public function getCardShape(string $shape = 'standard'): array
    {
        return config("card_display.card_shapes.{$shape}", config('card_display.card_shapes.standard'));
    }

    /**
     * Check if card display should be shown in unmasked mode
     *
     * @param ?int $userId User ID (for audit logging)
     * @param string $action Action being performed (display, export, email)
     * @return bool Whether to show unmasked
     */
    public function canShowUnmasked(?int $userId = null, string $action = 'display'): bool
    {
        $requireAuth = config('card_display.audit.require_auth', true);
        $requirePermission = config('card_display.audit.require_permission', 'admin');
        $dailyLimit = config('card_display.audit.daily_limit', null);

        if ($requireAuth && !auth()->check()) {
            return false;
        }

        if ($requirePermission && auth()->user() && !auth()->user()->hasPermissionTo($requirePermission)) {
            return false;
        }

        if ($dailyLimit && $userId) {
            $todayCount = $this->countTodayUnmaskedAccess($userId);
            if ($todayCount >= $dailyLimit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Count today's unmasked accesses for audit
     *
     * @param int $userId User ID
     * @return int Count
     */
    private function countTodayUnmaskedAccess(int $userId): int
    {
        // Implement audit logging query here if needed
        // For now, return 0 (no limit enforced)
        return 0;
    }

    /**
     * Log unmasked card access for audit trail
     *
     * @param int $cardId Card ID
     * @param int $userId User ID
     * @param string $action Action performed
     * @param array $metadata Additional metadata
     * @return bool Success
     */
    public function auditUnmaskedAccess(int $cardId, int $userId, string $action = 'view', array $metadata = []): bool
    {
        if (!config('card_display.audit.log_unmasked_access', true)) {
            return true;
        }

        // Log to database/audit trail
        // Implementation depends on audit logging system
        logger()->info('Unmasked card access', [
            'card_id' => $cardId,
            'user_id' => $userId,
            'action' => $action,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);

        return true;
    }
}
