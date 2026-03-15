/**
 * Unified formatting utilities
 * Replaces: dashboard/data helpers + data/helpers.formatPrice
 */

/** Arabic locale with Latin (Western) numerals — single source of truth */
export const AR_LOCALE = 'ar-SA-u-nu-latn';

/**
 * Format a number with Arabic-Saudi locale
 * @param {number} num
 * @returns {string}
 */
export function formatNumber( num ) {
    return new Intl.NumberFormat( AR_LOCALE ).format( num );
}

/**
 * Format a number as Saudi Riyal currency
 * @param {number} num
 * @returns {string}
 */
export function formatCurrency( num ) {
    return new Intl.NumberFormat( AR_LOCALE ).format( num ) + ' ر.س';
}

/**
 * Alias for formatCurrency — backward compatibility with public data/helpers.js
 * @param {number} price
 * @returns {string}
 */
export const formatPrice = formatCurrency;

/**
 * Format an ISO date string to Arabic locale
 * @param {string} dateStr - ISO date string or 'YYYY-MM-DD'
 * @param {Intl.DateTimeFormatOptions} [options]
 * @returns {string}
 */
export function formatDate( dateStr, options = {} ) {
    const defaults = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date( dateStr ).toLocaleDateString( AR_LOCALE, { ...defaults, ...options } );
}

/**
 * Get Arabic label for a status code
 * @param {string} status
 * @returns {string}
 */
export function getStatusLabel( status ) {
    /** @type {Record<string, string>} */
    const map = {
        active: 'ساري',
        pending: 'قيد الانتظار',
        expired: 'منتهي',
        reviewing: 'قيد المراجعة',
        approved: 'موافق عليه',
        rejected: 'مرفوض',
        paid: 'تم الدفع',
        success: 'ناجح',
        failed: 'فاشل',
        completed: 'مكتمل',
        customer_info: 'معلومات العميل',
        vehicle_info: 'معلومات المركبة',
        compare: 'مقارنة الأسعار',
        checkout: 'إتمام الشراء',
        payment: 'الدفع',
    };
    return map[ status ] || status;
}

/**
 * Get Tailwind color classes for a status code
 * @param {string} status
 * @returns {string}
 */
export function getStatusColor( status ) {
    /** @type {Record<string, string>} */
    const map = {
        active: 'bg-green-100 text-green-700',
        pending: 'bg-yellow-100 text-yellow-700',
        expired: 'bg-red-100 text-red-700',
        reviewing: 'bg-blue-100 text-blue-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        paid: 'bg-emerald-100 text-emerald-700',
        success: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
        completed: 'bg-emerald-100 text-emerald-700',
        customer_info: 'bg-sky-100 text-sky-700',
        vehicle_info: 'bg-indigo-100 text-indigo-700',
        compare: 'bg-purple-100 text-purple-700',
        checkout: 'bg-amber-100 text-amber-700',
        payment: 'bg-teal-100 text-teal-700',
    };
    return map[ status ] || 'bg-gray-100 text-gray-700';
}

/**
 * Format date + time for display
 * @param {string} dateStr
 * @returns {string}
 */
export function formatDateTime( dateStr ) {
    if ( !dateStr ) return '—';
    return new Date( dateStr ).toLocaleString( AR_LOCALE, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    } );
}

/**
 * Format currency in SAR (Intl format)
 * @param {number} amount
 * @returns {string}
 */
export function formatCurrencySAR( amount ) {
    if ( !amount ) return '—';
    return new Intl.NumberFormat( AR_LOCALE, {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0,
    } ).format( amount );
}

/**
 * Get relative time in Arabic (e.g., "منذ 5 دقائق")
 * @param {string} lastActivity - ISO date string
 * @returns {string}
 */
export function getTimeSinceActivity( lastActivity ) {
    if ( !lastActivity ) return '';
    const now = new Date();
    const last = new Date( lastActivity );
    const diffMs = now - last;
    const diffMins = Math.floor( diffMs / 60000 );

    if ( diffMins < 1 ) return 'الآن';
    if ( diffMins < 60 ) return `منذ ${ diffMins } دقيقة`;
    const diffHours = Math.floor( diffMins / 60 );
    if ( diffHours < 24 ) return `منذ ${ diffHours } ساعة`;
    const diffDays = Math.floor( diffHours / 24 );
    return `منذ ${ diffDays } يوم`;
}
