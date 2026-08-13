<?php

namespace App\Enums;

enum PaymentFailureReason: string
{
    /** Retained for historical records; new card rejections cannot select it. */
    case RAJHI_NOT_SUPPORTED = 'rajhi_not_supported';

    case INSUFFICIENT_FUNDS = 'insufficient_funds';
    case CARD_DECLINED = 'card_declined';
    case OTP_FAILED = 'otp_failed';
    case NETWORK_ERROR = 'network_error';

    /** @var list<string> */
    private const CARD_REJECTION_VALUES = [
        'card_invalid',
        'card_expired',
        'card_stolen',
        'card_mismatch',
        'card_insufficient_funds',
        'card_other',
    ];

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $reason): string => $reason->value,
            self::cases(),
        );
    }

    /**
     * Reasons that an administrator may select when rejecting a payment card.
     *
     * @return list<string>
     */
    public static function cardRejectionValues(): array
    {
        return self::CARD_REJECTION_VALUES;
    }

    public static function normalizeForApi(?string $reason): string
    {
        $reason = trim((string) $reason);

        if (in_array($reason, self::recognizedValues(), true)) {
            return $reason;
        }

        return self::CARD_DECLINED->value;
    }

    /**
     * @return array{
     *     reason: string,
     *     type: 'error'|'warning',
     *     retryable: bool,
     *     title: string,
     *     message: string,
     *     action: 'retry_later'|'retry_payment'|'use_another_card',
     *     action_text: string,
     *     suggestion: string|null
     * }
     */
    public static function meta(?string $reason): array
    {
        $normalized = self::normalizeForApi($reason);

        return [
            'reason' => $normalized,
            ...self::definitionFor($normalized),
        ];
    }

    /** @return list<string> */
    private static function recognizedValues(): array
    {
        return [...self::values(), ...self::CARD_REJECTION_VALUES];
    }

    /**
     * @return array{
     *     type: 'error'|'warning',
     *     retryable: bool,
     *     title: string,
     *     message: string,
     *     action: 'retry_later'|'retry_payment'|'use_another_card',
     *     action_text: string,
     *     suggestion: string|null
     * }
     */
    private static function definitionFor(string $reason): array
    {
        return match ($reason) {
            self::RAJHI_NOT_SUPPORTED->value => [
                'type' => 'warning',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'حالياً لا نقبل المدفوعات الإلكترونية عبر بطاقات مصرف الراجحي بسبب خلل تقني.',
                'action' => 'use_another_card',
                'action_text' => 'يرجى استخدام بطاقة بنكية أخرى لإتمام العملية.',
                'suggestion' => 'استخدم بطاقة Visa أو Mastercard أو mada من بنك آخر.',
            ],
            self::INSUFFICIENT_FUNDS->value => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'الرصيد غير كافٍ لإتمام العملية.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى استخدام بطاقة أخرى أو إعادة المحاولة بعد تغذية الرصيد.',
                'suggestion' => null,
            ],
            self::OTP_FAILED->value => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى إعادة المحاولة باستخدام رمز تحقق جديد.',
                'suggestion' => null,
            ],
            self::NETWORK_ERROR->value => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'حدث خطأ تقني أثناء معالجة الطلب.',
                'action' => 'retry_later',
                'action_text' => 'يرجى المحاولة لاحقاً.',
                'suggestion' => null,
            ],
            'card_invalid' => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'بيانات البطاقة غير صحيحة.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى التحقق من رقم البطاقة وتاريخ الانتهاء ورمز الأمان.',
                'suggestion' => null,
            ],
            'card_expired' => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'البطاقة منتهية الصلاحية.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى استخدام بطاقة صالحة لإتمام العملية.',
                'suggestion' => null,
            ],
            'card_stolen' => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'تعذر اعتماد بيانات البطاقة.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى استخدام بطاقة أخرى لإتمام العملية.',
                'suggestion' => null,
            ],
            'card_mismatch' => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'بيانات البطاقة لا تتطابق.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى مراجعة بيانات البطاقة ثم إعادة المحاولة.',
                'suggestion' => null,
            ],
            'card_insufficient_funds' => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'رصيد البطاقة غير كافٍ.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى استخدام بطاقة أخرى أو إعادة المحاولة بعد تغذية الرصيد.',
                'suggestion' => null,
            ],
            'card_other' => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'تعذر اعتماد بيانات الدفع الحالية.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى استخدام بطاقة أخرى أو المحاولة لاحقاً.',
                'suggestion' => null,
            ],
            default => [
                'type' => 'error',
                'retryable' => true,
                'title' => 'تعذر متابعة طلب الدفع',
                'message' => 'لم نتمكن من إكمال التحقق من بيانات الدفع الحالية.',
                'action' => 'retry_payment',
                'action_text' => 'يرجى استخدام بطاقة أخرى أو مراجعة البيانات والمحاولة مرة أخرى.',
                'suggestion' => null,
            ],
        };
    }
}
