/**
 * Shared company logos composable
 *
 * Eagerly loads all insurance company logos once and provides a fast
 * Map-based lookup by companyId. Replaces the duplicated import.meta.glob +
 * getCompanyLogo pattern in ComparePage, DetailsPage, and CheckoutPage.
 */
import { getCompany } from '@/data';

// Eagerly load all logos once at module level
const logoModules = import.meta.glob( '/resources/images/logo/insurance_company/*.{png,webp}', {
    eager: true,
    import: 'default',
} );

// Pre-build a Map<companyId, url> for O(1) lookup
const logoCache = new Map();

/**
 * Get the logo URL for a company by its ID.
 * Uses a pre-computed cache for O(1) lookups instead of O(n) Object.keys().find() per call.
 *
 * @param {number} companyId
 * @returns {string} — logo URL or empty string
 */
export function getCompanyLogo( companyId ) {
    if ( logoCache.has( companyId ) ) {
        return logoCache.get( companyId );
    }

    const company = getCompany( companyId );
    if ( !company || !company.image ) {
        logoCache.set( companyId, '' );
        return '';
    }

    const key = Object.keys( logoModules ).find( k => k.endsWith( '/' + company.image ) );
    const url = key ? logoModules[ key ] : '';
    logoCache.set( companyId, url );
    return url;
}
