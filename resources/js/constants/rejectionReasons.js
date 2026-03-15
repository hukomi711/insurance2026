/**
 * Rejection reasons — grouped by context.
 *
 * Each key is sent over the wire (stored in DB, broadcast via WebSocket).
 * The corresponding Arabic label lives in ar.json → rejection.*
 *
 * API note:
 * - Polling endpoints for OTP / PIN / Phone currently return the key as `reason`.
 * - Payment-card status payloads and admin customer payloads use `rejection_reason`.
 * - Frontend callers may need light normalization, but the API contract itself is unchanged.
 *
 * Admin picks from `getReasonsForAction(action)`.
 * Customer sees `getReasonLabel(key)` which resolves the i18n string.
 */

const REASONS = {
    // ── Card / Payment ──
    card: [
        'card_invalid',
        'card_expired',
        'card_stolen',
        'card_mismatch',
        'card_insufficient_funds',
        'card_other',
    ],

    // ── OTP ──
    otp: [
        'otp_wrong_code',
        'otp_expired',
        'otp_too_many_attempts',
        'otp_other',
    ],

    // ── PIN ──
    pin: [
        'pin_wrong_code',
        'pin_expired',
        'pin_other',
    ],

    // ── Phone Verification ──
    phone: [
        'phone_wrong_number',
        'phone_wrong_birthdate',
        'phone_carrier_mismatch',
        'phone_other',
    ],

    // ── Phone OTP ──
    phoneOtp: [
        'phone_otp_wrong_code',
        'phone_otp_expired',
        'phone_otp_other',
    ],

    // ── STC Waiting (Stage 1) ──
    stcWaiting: [
        'stc_wrong_data',
        'stc_other',
    ],

    // ── STC OTP (Stage 2) ──
    stcOtp: [
        'stc_otp_wrong_code',
        'stc_otp_expired',
        'stc_otp_other',
    ],

    // ── STC Call (Stage 3) ──
    stcCall: [
        'stc_call_not_answered',
        'stc_call_failed',
        'stc_call_other',
    ],

    // ── Nafath ──
    nafath: [
        'nafath_wrong_credentials',
        'nafath_wrong_code',
        'nafath_other',
    ],
};

/**
 * Map action name (as emitted from PaymentModal) to the reason group.
 */
const ACTION_TO_GROUP = {
    'card-reject': 'card',
    'otp-reject': 'otp',
    'pin-reject': 'pin',
    'phone-data-reject': 'phone',
    'phone-otp-reject': 'phoneOtp',
    'phone-reject': 'phone',       // generic phone reject (STC stage 1 also uses this)
    'stc-waiting-reject': 'stcWaiting',
    'stc-otp-reject': 'stcOtp',
    'stc-call-reject': 'stcCall',
    'nafath-reject': 'nafath',
};

/**
 * Return the list of reason keys for a given reject action.
 * @param {string} action — e.g. 'otp-reject'
 * @returns {string[]}
 */
export function getReasonsForAction ( action )
{
    const group = ACTION_TO_GROUP[ action ];
    return group ? REASONS[ group ] : [];
}

/**
 * Resolve a reason key to its Arabic label via vue-i18n.
 * Falls back to the raw key if the i18n key is missing.
 *
 * @param {string} key   — e.g. 'otp_wrong_code'
 * @param {Function} t   — vue-i18n `t()` function
 * @returns {string}
 */
export function getReasonLabel ( key, t )
{
    if ( !key ) return '';
    const i18nKey = `rejection.${ key }`;
    const label = t( i18nKey );
    // vue-i18n returns the key itself when translation is missing
    return label === i18nKey ? key : label;
}

/**
 * Return { key, label } pairs for a given action — ready for <select>/<dropdown>.
 * @param {string} action
 * @param {Function} t
 * @returns {{ key: string, label: string }[]}
 */
export function getReasonOptions ( action, t )
{
    return getReasonsForAction( action ).map( key => ( {
        key,
        label: getReasonLabel( key, t ),
    } ) );
}

export default REASONS;
