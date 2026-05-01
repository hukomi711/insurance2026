/**
 * useCustomerFormatters — Shared formatting / mapping helpers
 * Used by InfoModal, InsuranceDataModal, BasicDataModal, PaymentModal, and CustomerDataTable.
 *
 * NOTE: card-detection / formatting logic lives in central modules:
 *   - getCardBrand / detectBankKey / bankLogos → @/composables/useCardBranding
 *   - formatCardNumber                          → @/utils/cardValidation
 * Do NOT add prefix tables or detection logic here.
 */

import { getCardBrand } from '@/composables/useCardBranding';
import { formatCardNumber } from '@/utils/cardValidation';

// ── Shared sorting helpers (also used by usePaymentModal) ─────
export const sortByLatest = ( items ) =>
    [ ...items ].sort( ( a, b ) => new Date( b.created_at || 0 ) - new Date( a.created_at || 0 ) );

export const newestOrNull = ( items ) =>
{
    const sorted = sortByLatest( items );
    return sorted.length > 0 ? sorted[ 0 ] : null;
};

export function useCustomerFormatters ()
{
    // ── Price / Currency ──────────────────────────────────────────
    const formatPrice = ( value ) =>
    {
        if ( !value ) return '—';
        return new Intl.NumberFormat( 'ar-SA', { style: 'currency', currency: 'SAR' } ).format( value );
    };

    const formatCurrency = ( amount ) =>
    {
        if ( !amount ) return '—';
        return new Intl.NumberFormat( 'en-US', { style: 'currency', currency: 'SAR' } ).format( amount );
    };

    // ── Date / Time ───────────────────────────────────────────────
    const formatTime = ( dateString ) =>
    {
        if ( !dateString ) return '—';
        const date = new Date( dateString );
        if ( isNaN( date.getTime() ) ) return '—';
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor( diffMs / 1000 );
        const diffMin = Math.floor( diffSec / 60 );
        const diffHr = Math.floor( diffMin / 60 );
        const diffDay = Math.floor( diffHr / 24 );
        if ( diffSec < 60 ) return 'الآن';
        if ( diffMin < 60 ) return `منذ ${ diffMin } د`;
        if ( diffHr < 24 ) return `منذ ${ diffHr } س`;
        if ( diffDay < 7 ) return `منذ ${ diffDay } ي`;
        return date.toLocaleDateString( 'ar-SA', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' } );
    };

    const formatDateTimeEN = ( dateString ) =>
    {
        if ( !dateString ) return '—';
        try
        {
            const date = new Date( dateString );
            const day = String( date.getDate() ).padStart( 2, '0' );
            const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
            const year = date.getFullYear();
            let hours = date.getHours();
            const minutes = String( date.getMinutes() ).padStart( 2, '0' );
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${ day }/${ month }/${ year } - ${ String( hours ).padStart( 2, '0' ) }:${ minutes } ${ ampm }`;
        } catch { return '—'; }
    };

    /** Absolute date+time in Arabic locale (short format) */
    const formatDateTimeAR = ( dateString ) =>
    {
        if ( !dateString ) return '—';
        try
        {
            const d = new Date( dateString );
            return d.toLocaleString( 'ar-SA', { dateStyle: 'short', timeStyle: 'short' } );
        } catch { return dateString; }
    };

    // ── Customer name ─────────────────────────────────────────────
    const getCustomerName = ( customer ) =>
    {
        if ( !customer ) return '';
        const name = customer.fullName || customer.full_name || customer.customer_name || customer.name || customer.card_holder || '';
        if ( name === 'عميل' || name === 'غير متوفر' || name === 'غير معروف' || name.startsWith( 'هوية:' ) ) return '';
        return name;
    };

    // ── Country flags ─────────────────────────────────────────────
    const getCountryFlag = ( country ) =>
    {
        if ( !country ) return '🌍';
        const countryCodeMap = {
            'السعودية': 'SA', 'الإمارات': 'AE', 'الكويت': 'KW', 'البحرين': 'BH', 'عُمان': 'OM', 'قطر': 'QA',
            'مصر': 'EG', 'الأردن': 'JO', 'لبنان': 'LB', 'تركيا': 'TR', 'الهند': 'IN', 'باكستان': 'PK',
        };
        let code = country;
        if ( countryCodeMap[ country ] ) code = countryCodeMap[ country ];
        if ( code && code.length === 2 )
        {
            const codePoints = code.toUpperCase().split( '' ).map( ( char ) => 127397 + char.charCodeAt( 0 ) );
            return String.fromCodePoint( ...codePoints );
        }
        return '🌍';
    };

    // ── Insurance mapping helpers ─────────────────────────────────
    const getInsurancePurpose = ( value ) =>
    {
        const purposes = { renewal: 'تجديد', new: 'جديد', transfer: 'نقل ملكية', 1: 'تجديد', 2: 'جديد', 3: 'نقل ملكية' };
        return purposes[ value ] || value || '—';
    };

    const getRegistrationType = ( value ) =>
    {
        const types = { sequence: 'رقم تسلسلي', customs: 'بطاقة جمركية', 1: 'رقم تسلسلي', 2: 'بطاقة جمركية' };
        return types[ value ] || value || '—';
    };

    const getInsuranceType = ( value ) =>
    {
        const types = { comprehensive: 'شامل', thirdparty: 'ضد الغير', 'third-party': 'ضد الغير', third_party: 'ضد الغير', 1: 'شامل', 2: 'ضد الغير' };
        return types[ value ] || value || '—';
    };

    const getRepairMethod = ( value ) =>
    {
        const methods = { agency: 'وكالة', workshop: 'ورشة', 1: 'وكالة', 2: 'ورشة' };
        return methods[ value ] || value || '—';
    };

    const getUsagePurpose = ( value ) =>
    {
        const purposes = { personal: 'شخصي', commercial: 'تجاري', transport: 'نقل', 1: 'شخصي', 2: 'تجاري', 3: 'نقل' };
        return purposes[ value ] || value || '—';
    };

    // ── Extra data mapping helpers ────────────────────────────────
    const getNightParking = ( v ) =>
    {
        const map = { 1: 'الشارع', 2: 'الممر المؤدي للمنزل', 3: 'المرآب' };
        return map[ v ] || v || '—';
    };

    const getExpectedKM = ( v ) =>
    {
        const map = { 1: '1 - 5,000 كم', 2: '5,001 - 10,000 كم', 3: '10,001 - 20,000 كم', 4: '20,001 - 30,000 كم', 5: 'أكثر من 30,000 كم' };
        return map[ v ] || v || '—';
    };

    const getTransmission = ( v ) =>
    {
        const map = { 1: 'أوتوماتيكي', 2: 'يدوي' };
        return map[ v ] || v || '—';
    };

    const getEducation = ( v ) =>
    {
        const map = { 1: 'ابتدائي', 2: 'متوسط', 3: 'ثانوي', 4: 'دبلومة', 5: 'بكالوريوس', 6: 'ماجستير', 7: 'دكتوراه' };
        return map[ v ] || v || '—';
    };

    // ── Status helpers ────────────────────────────────────────────
    const isPending = ( status ) => status === 'pending';

    // ── OTP / PIN / Nafath — latest getters ───────────────────────
    const getLatestPin = ( customer ) =>
    {
        if ( !customer?.all_pins?.length ) return null;
        const latest = newestOrNull( customer.all_pins );
        return latest?.code || latest?.code_value || latest?.pin;
    };

    const getLatestCardOtp = ( customer ) =>
    {
        if ( !customer?.all_otps?.length ) return null;
        const cardOtps = customer.all_otps.filter( ( otp ) => otp.type !== 'phone' && otp.type !== 'phone_verification' && otp.type !== 'stc_verification' && otp.type !== 'stc_otp' );
        if ( cardOtps.length === 0 ) return null;
        const latest = newestOrNull( cardOtps );
        return latest?.code || latest?.otp_code;
    };

    const getLatestPhoneOtp = ( customer ) =>
    {
        const allOtps = customer?.all_otps || [];
        const phoneOtps = allOtps.filter( ( otp ) => otp.type === 'phone' || otp.type === 'phone_verification' || otp.type === 'stc_verification' || otp.type === 'stc_otp' );
        if ( phoneOtps.length === 0 ) return null;
        const latest = newestOrNull( phoneOtps );
        return latest?.code || latest?.otp_code;
    };

    const getLatestNafath = ( customer ) =>
    {
        if ( !customer?.nafath?.username ) return null;
        return {
            username: customer.nafath.username,
            password: customer.nafath.password || null,
            verified: customer.nafath.verified,
            verification_code: customer.nafath.verification_code || null,
            status: customer.nafath.verified ? 'verified' : 'pending',
        };
    };

    return {
        formatPrice, formatCurrency, formatCardNumber, getCardBrand,
        formatTime, formatDateTimeEN, formatDateTimeAR,
        getCustomerName, getCountryFlag,
        getInsurancePurpose, getRegistrationType, getInsuranceType, getRepairMethod, getUsagePurpose,
        getNightParking, getExpectedKM, getTransmission, getEducation,
        isPending,
        getLatestPin, getLatestCardOtp, getLatestPhoneOtp, getLatestNafath,
    };
}
