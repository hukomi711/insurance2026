/**
 * Unified pricing utility functions
 *
 * Single source of truth for VAT calculation, price formatting, and pricing-related helpers.
 * Replaces duplicated VAT logic across DetailsPage, CheckoutPage, and OfferDetailsSheet.
 */

/** نسبة ضريبة القيمة المضافة */
export const VAT_RATE = 0.15;

/**
 * حساب ضريبة القيمة المضافة
 * @param {number} subtotal - المبلغ قبل الضريبة (يشمل الإضافات)
 * @param {number} [rate=0.15] - نسبة الضريبة
 * @returns {number} مبلغ الضريبة (مقرّب)
 */
export function calculateVAT ( subtotal, rate = VAT_RATE )
{
    return Math.round( subtotal * rate );
}

/**
 * حساب الإجمالي شامل الضريبة
 * @param {number} basePrice - السعر الأساسي السنوي
 * @param {number} [addonsTotal=0] - مجموع أسعار الإضافات
 * @param {number} [rate=0.15] - نسبة الضريبة
 * @returns {{ subtotal: number, vat: number, total: number }}
 */
export function calculateTotalWithVAT ( basePrice, addonsTotal = 0, rate = VAT_RATE )
{
    const subtotal = basePrice + addonsTotal;
    const vat = calculateVAT( subtotal, rate );
    return {
        subtotal,
        vat,
        total: subtotal + vat,
    };
}

/**
 * حساب السعر الشهري الدقيق
 * يستخدم Math.ceil لضمان عدم وجود فرق عند الضرب في 12
 * مع ملاحظة أن المبلغ الشهري تقريبي
 *
 * @param {number} annualPrice - السعر السنوي
 * @returns {number} السعر الشهري
 */
export function calculateMonthlyPrice ( annualPrice )
{
    return Math.ceil( annualPrice / 12 );
}
