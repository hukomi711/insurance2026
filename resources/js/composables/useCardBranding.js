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
const bankLogoFiles = import.meta.glob( '../../images/logo/banks/*.svg', {
    eager: true,
    import: 'default',
} );

import visaLogo from '../../images/logo/summary_logo/Visa_2021.svg';
import mcLogo from '../../images/logo/summary_logo/ma_symbol.png';
import madaLogo from '../../images/logo/summary_logo/Mada-01.png';

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

// ── BIN → Bank mapping (common Saudi bank BIN prefixes) ─────────────
const BIN_BANK_MAP = [
    // مصرف الراجحي — Al Rajhi
    {
        prefixes: [
            '458618', '468564', '468565', '521076', '524940', '527016',
            '543357', '553680', '588845', '440647', '427010', '427011',
            '457997', '458456', '486654', '412943', '432415',
            '453201', '453286', '434688', '532580',
        ],
        bank: 'rajhi',
    },
    // البنك الأهلي — SNB (Al Ahli)
    {
        prefixes: [
            '489536', '409201', '431361', '439954', '432328', '428671',
            '462220', '455708', '486094', '490032', '410820', '455036',
            '422820', '422821',
        ],
        bank: 'ahli',
    },
    // مصرف الإنماء — Alinma
    {
        prefixes: [
            '485824', '485825', '485823', '636120', '968205',
            '485826', '485827',
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
            '468540', '468541', '412565', '423766', '483510',
        ],
        bank: 'jazira',
    },
    // بنك الرياض — Riyad Bank
    {
        prefixes: [
            '417633', '417634', '421141', '422817', '439357',
            '489318', '420651', '428331',
        ],
        bank: 'riyad',
    },
    // بنك البلاد — Bank AlBilad
    {
        prefixes: [
            '402962', '432237', '407197', '403888',
        ],
        bank: 'bilad',
    },
    // البنك العربي الوطني — ANB
    {
        prefixes: [
            '431062', '403024', '406136', '419593', '432156',
        ],
        bank: 'anb',
    },
    // البنك السعودي للاستثمار — SAIB
    {
        prefixes: [
            '410621', '420259', '450290',
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
];

// ── Card brand detection ────────────────────────────────────────────
function getCardBrand ( number )
{
    if ( !number ) return 'unknown';
    const cleaned = String( number ).replace( /\s/g, '' );
    const d1 = cleaned.charAt( 0 );
    const d2 = cleaned.substring( 0, 2 );
    const d4 = cleaned.substring( 0, 4 );

    // Mada (more specific — check first)
    const madaPrefixes = [
        '588845', '440647', '440795', '446404', '457865',
        '968540', '588846', '968201',
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

export { getCardBrand, getBankByBin, networkLogos, bankLogos, BANKS };
