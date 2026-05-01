/**
 * bankDetector — thin compatibility wrapper.
 *
 * The single source of truth for BIN → bank mapping is
 * `resources/js/composables/useCardBranding.js` (BIN_BANK_MAP). This module
 * exists only so non-Vue callers (e.g. PaymentWaitingPage, PaymentModal,
 * PaymentMethodCard) can import a plain function with the historical signature.
 *
 * DO NOT re-introduce a local prefix table here. If a bank prefix needs to
 * change, update BIN_BANK_MAP inside useCardBranding.js (and
 * config/bank_bins.php for the backend) — both must stay in lockstep.
 */

import { detectBankKey } from '@/composables/useCardBranding';

/**
 * Detect Saudi bank key from a card number / BIN.
 *
 * @param {string} cardNumber  Full PAN, 6/8-digit BIN, or any prefix.
 * @returns {string|null}      Bank key (rajhi, ahli, inma, …) or null when
 *                             fewer than 6 digits are available or no match.
 */
export function detectBankFromBin ( cardNumber = '' )
{
    const digits = String( cardNumber ).replace( /\D/g, '' );
    if ( digits.length < 6 ) return null;
    return detectBankKey( digits ) || null;
}
