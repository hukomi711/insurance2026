/**
 * 6-digit BIN prefixes per bank — mirrors config/bank_bins.php.
 * Keys match BANK_LOGOS in constants/bankLogos.js.
 */
const BIN_MAP = {
    rajhi: [
        '458618', '468564', '468565', '521076', '524940', '527016',
        '543357', '553680', '588845', '440647', '427010', '427011',
        '457997', '458456', '486654', '412943', '432415',
        '453201', '453286', '434688', '532580', '414627', '445827',
    ],
    ahli: [
        '489536', '409201', '431361', '439954', '432328', '428671',
        '462220', '455708', '486094', '490032', '410820', '455036',
        '422820', '422821',
    ],
    inma: [
        '485824', '485825', '485823', '636120', '968205',
        '485826', '485827',
    ],
    sabb: [
        '401757', '410685', '420132', '431313', '474491',
        '423854', '447264',
    ],
    jazira: [
        '468540', '468541', '412565', '423766', '483510',
    ],
    riyad: [
        '417633', '417634', '421141', '422817', '439357',
        '489318', '420651', '428331',
    ],
    bilad: [
        '402962', '432237', '407197', '403888',
    ],
    anb: [
        '431062', '403024', '406136', '419593', '432156',
    ],
    saib: [
        '410621', '420259', '450290',
    ],
    bsf: [
        '440795', '446404', '457865', '403941', '406996', '489317',
    ],
    gib: [
        '403635', '404610', '417564', '468544',
    ],
};

/** Pre-built lookup: bin → bankKey (O(1) instead of iterating rules) */
const _BIN_LOOKUP = Object.create( null );
for ( const [ bank, prefixes ] of Object.entries( BIN_MAP ) )
{
    for ( const prefix of prefixes ) _BIN_LOOKUP[ prefix ] = bank;
}

export function detectBankFromBin ( cardNumber = '' )
{
    const digits = String( cardNumber ).replace( /\D/g, '' );
    const bin = digits.slice( 0, 6 );

    if ( bin.length < 6 ) return null;

    return _BIN_LOOKUP[ bin ] || null;
}
