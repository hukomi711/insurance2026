export function normalizeOtp ( value, maxLength ) {
    return String( value ?? '' )
        .replace( /[٠-٩]/g, digit => String( digit.charCodeAt( 0 ) - 0x0660 ) )
        .replace( /[۰-۹]/g, digit => String( digit.charCodeAt( 0 ) - 0x06F0 ) )
        .replace( /\D/g, '' )
        .slice( 0, maxLength );
}

export function normalizeAcceptedLengths ( lengths, maxLength ) {
    if ( !Array.isArray( lengths ) ) return [];

    return [ ...new Set( lengths ) ]
        .filter( length => Number.isInteger( length ) && length > 0 && length <= maxLength )
        .sort( ( a, b ) => a - b );
}
