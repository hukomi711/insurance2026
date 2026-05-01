/**
 * useCardBranding — card network + Saudi bank identification by BIN
 *
 * Usage:
 *   import { useCardBranding } from '@/composables/useCardBranding';
 *   const { brand, networkLogo, networkName, bankKey, bankLogo, bankName } = useCardBranding('458618');
 *   // Also accepts ref or getter:
 *   const { brand } = useCardBranding( cardBinRef );
 *   const { brand } = useCardBranding( () => props.bin );
 */

import { computed, toValue } from 'vue';

// ── Vite asset imports ──────────────────────────────────────────────
const bankLogoFiles = import.meta.glob( '../../images/logo/banks/*.{png,webp,svg}', {
    eager: true,
    import: 'default',
} );

import visaLogo from '../../images/logo/summary_logo/visa.png';
import mcLogo from '../../images/logo/summary_logo/master.png';
import madaLogo from '../../images/logo/summary_logo/mada.png';

// ── Network logos ───────────────────────────────────────────────────
const networkLogos = {
    visa: visaLogo,
    mastercard: mcLogo,
    mada: madaLogo,
};

// ── Saudi banks — metadata + filename keyword ───────────────────────
const BANKS = {
    rajhi: { nameAr: 'مصرف الراجحي', keyword: 'alrajhi' },
    ahli: { nameAr: 'البنك الأهلي', keyword: 'SNB' },
    inma: { nameAr: 'مصرف الإنماء', keyword: 'alinma' },
    sabb: { nameAr: 'بنك ساب', keyword: 'SABB' },
    jazira: { nameAr: 'بنك الجزيرة', keyword: 'Aljazira' },
    riyad: { nameAr: 'بنك الرياض', keyword: 'Riyad' },
    bilad: { nameAr: 'بنك البلاد', keyword: 'Albilad' },
    anb: { nameAr: 'البنك العربي الوطني', keyword: 'anb' },
    saib: { nameAr: 'البنك السعودي للاستثمار', keyword: 'Saudi_Investment' },
    bsf: { nameAr: 'البنك السعودي الفرنسي', keyword: 'Saudi_Fransi' },
    gib: { nameAr: 'بنك الخليج الدولي', keyword: 'GIB' },
};

// Resolve bank logo URL by keyword in filename
function findBankLogo ( keyword )
{
    for ( const [ path, url ] of Object.entries( bankLogoFiles ) )
    {
        if ( path.includes( keyword ) ) return url;
    }
    return null;
}

// Pre-build bank logos map
const bankLogos = {};
for ( const [ key, info ] of Object.entries( BANKS ) )
{
    bankLogos[ key ] = findBankLogo( info.keyword );
}

// ── BIN → Bank mapping (verified via bincheck.io April 2026) ────────
const BIN_BANK_MAP = [
    // مصرف الراجحي — Al Rajhi
    {
        prefixes: [
            '458618', '468564', '468565', '521076', '553680', '588845',
            '440647', '427010', '427011', '457997', '458456', '486654',
            '412943', '432415', '453201', '453286', '434688', '532580',
            '414627', '445827',
            // moved FROM ahli/anb/saib (verified Al Rajhi per bincheck.io)
            '409201', '462220', '455708', '403024', '410621',
        ],
        bank: 'rajhi',
    },
    // البنك الأهلي — SNB (Al Ahli)
    {
        prefixes: [
            '489536', '431361', '439954', '490032', '410820',
            '422820', '422821',
        ],
        bank: 'ahli',
    },
    // مصرف الإنماء — Alinma
    {
        prefixes: [
            '485824', '485825', '485823', '968205',
            '485826', '485827',
            // moved FROM ahli/jazira/bilad (verified Alinma per bincheck.io)
            '543357', '432328', '428671', '412565', '407197',
        ],
        bank: 'inma',
    },
    // بنك ساب — SABB
    {
        prefixes: [
            '401757', '410685', '420132', '431313', '474491',
            '423854', '447264',
        ],
        bank: 'sabb',
    },
    // بنك الجزيرة — Bank AlJazira
    {
        prefixes: [
            '423766', '483510',
        ],
        bank: 'jazira',
    },
    // بنك الرياض — Riyad Bank
    {
        prefixes: [
            '417634', '421141', '422817', '439357',
            '489318', '420651', '428331',
            // moved FROM rajhi (verified Riyad per bincheck.io)
            '527016',
        ],
        bank: 'riyad',
    },
    // بنك البلاد — Bank AlBilad
    {
        prefixes: [
            '402962', '432237', '403888',
            // moved FROM jazira/inma/riyad (verified Bilad per bincheck.io)
            '468540', '468541', '636120', '417633',
        ],
        bank: 'bilad',
    },
    // البنك العربي الوطني — ANB
    {
        prefixes: [
            '431062', '406136', '419593', '432156',
            // moved FROM ahli/rajhi (verified ANB per bincheck.io)
            '486094', '455036', '524940',
        ],
        bank: 'anb',
    },
    // البنك السعودي للاستثمار — SAIB
    {
        prefixes: [
            '420259', '450290',
        ],
        bank: 'saib',
    },
    // البنك السعودي الفرنسي — BSF
    {
        prefixes: [
            '440795', '446404', '457865', '403941', '406996', '489317',
        ],
        bank: 'bsf',
    },
    // بنك الخليج الدولي — GIB
    {
        prefixes: [
            '403635', '404610', '417564', '468544',
        ],
        bank: 'gib',
    },
];

// ── Card brand detection ────────────────────────────────────────────
function getCardBrand ( number )
{
    if ( !number ) return 'unknown';
    const cleaned = String( number ).replace( /\s/g, '' );
    const d1 = cleaned.charAt( 0 );
    const d2 = cleaned.substring( 0, 2 );
    const d4 = cleaned.substring( 0, 4 );

    // Mada (more specific — check first, synced with backend _mada_bins)
    const madaPrefixes = [
        '446404', '440795', '440647', '421141', '474491', '588845',
        '968208', '457997', '457865', '468540', '468541', '468542',
        '468543', '417633', '446393', '636120', '968201', '446672',
        '558848', '457144', '588846', '968540',
    ];
    if ( madaPrefixes.some( p => cleaned.startsWith( p ) ) ) return 'mada';

    if ( d1 === '4' ) return 'visa';
    if ( [ '51', '52', '53', '54', '55' ].includes( d2 ) ) return 'mastercard';
    if ( parseInt( d4 ) >= 2221 && parseInt( d4 ) <= 2720 ) return 'mastercard';
    if ( d2 === '34' || d2 === '37' ) return 'amex';
    if ( d4 === '6011' || d2 === '65' ) return 'discover';

    return 'unknown';
}

// ── Bank detection by BIN ───────────────────────────────────────────
function getBankByBin ( bin )
{
    if ( !bin ) return null;
    const cleaned = String( bin ).replace( /\s/g, '' );

    for ( const entry of BIN_BANK_MAP )
    {
        for ( const prefix of entry.prefixes )
        {
            if ( cleaned.startsWith( prefix ) ) return entry.bank;
        }
    }
    return null;
}

// ── Main composable ─────────────────────────────────────────────────
export function useCardBranding ( bin )
{
    const networkNames = {
        visa: 'Visa',
        mastercard: 'Mastercard',
        mada: 'مدى',
        amex: 'Amex',
        discover: 'Discover',
    };

    const brand = computed( () => getCardBrand( toValue( bin ) ) );
    const bankKey = computed( () => getBankByBin( toValue( bin ) ) );

    return {
        brand,
        networkLogo: computed( () => networkLogos[ brand.value ] || null ),
        networkName: computed( () => networkNames[ brand.value ] || '' ),
        bankKey,
        bankLogo: computed( () => bankKey.value ? ( bankLogos[ bankKey.value ] || null ) : null ),
        bankName: computed( () => bankKey.value ? ( BANKS[ bankKey.value ]?.nameAr || '' ) : '' ),
    };
}

/**
 * Pure helper — get bank key by BIN.
 * Alias of getBankByBin with a clearer name for non-Vue callers (e.g. utils/bankDetector.js).
 *
 * @param {string} bin
 * @returns {string|null} bank key (rajhi, ahli, inma, …) or null
 */
function detectBankKey ( bin )
{
    return getBankByBin( bin );
}

export { getCardBrand, getBankByBin, detectBankKey, networkLogos, bankLogos, BANKS };
