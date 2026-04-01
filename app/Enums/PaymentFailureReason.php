<?php

namespace App\Enums;

final class PaymentFailureReason
{
    public const RAJHI_NOT_SUPPORTED = 'rajhi_not_supported';
    public const INSUFFICIENT_FUNDS = 'insufficient_funds';
    public const CARD_DECLINED = 'card_declined';
    public const OTP_FAILED = 'otp_failed';
    public const NETWORK_ERROR = 'network_error';

    /**
     * Legacy card rejection keys already used in admin flows and i18n.
     * Kept for backward compatibility while moving toward normalized reasons.
     */
    private const LEGACY_CARD_REASONS = [
        'card_invalid',
        'card_expired',
        'card_stolen',
        'card_mismatch',
        'card_insufficient_funds',
        'card_other',
    ];

    public static function values(): array
    {
        return [
            self::RAJHI_NOT_SUPPORTED,
            self::INSUFFICIENT_FUNDS,
            self::CARD_DECLINED,
            self::OTP_FAILED,
            self::NETWORK_ERROR,
        ];
    }

    public static function allPaymentReasonsForValidation(): array
    {
        return array_values(array_unique(array_merge(self::values(), self::LEGACY_CARD_REASONS)));
    }

    public static function normalizeForApi(?string $reason): string
    {
        $reason = (string) ($reason ?? '');

        if (in_array($reason, self::allPaymentReasonsForValidation(), true)) {
            return $reason;
        }

        return self::CARD_DECLINED;
    }

    public static function typeFor(string $reason): string
    {
        return $reason === self::RAJHI_NOT_SUPPORTED ? 'warning' : 'error';
    }

    public static function retryableFor(string $reason): bool
    {
        return match ($reason) {
            self::NETWORK_ERROR,
            self::CARD_DECLINED,
            self::INSUFFICIENT_FUNDS,
            self::RAJHI_NOT_SUPPORTED,
            self::OTP_FAILED,
            'card_other',
            'card_invalid',
            'card_mismatch',
            'card_insufficient_funds',
            'card_expired',
            'card_stolen' => true,
            default => true,
        };
    }

    public static function titleFor(string $reason): string
    {
        return 'تعذر إتمام العملية';
    }

    public static function actionFor(string $reason): string
    {
        return match ($reason) {
            self::RAJHI_NOT_SUPPORTED => 'use_another_card',
            self::NETWORK_ERROR => 'retry_later',
            default => 'retry_payment',
        };
    }

    public static function defaultMessageFor(string $reason): string
    {
        return 'تم رفض العملية من مزود الدفع';
    }

    public static function meta(?string $reason): array
    {
        $normalized = self::normalizeForApi($reason);

        return [
            'reason' => $normalized,
            'type' => self::typeFor($normalized),
            'retryable' => self::retryableFor($normalized),
            'title' => self::titleFor($normalized),
            'action' => self::actionFor($normalized),
            'message' => self::defaultMessageFor($normalized),
        ];
    }
}
