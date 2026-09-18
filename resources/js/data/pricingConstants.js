/**
 * Active fixed-pricing constants.
 *
 * Final formula (pre-VAT):
 * company base price + comprehensive fixed gap (if applicable)
 * + deductible increase + selected addons total
 */

/** VAT rate */
export const VAT_RATE = 0.15;

/**
 * Fixed base prices per supported company.
 * companyId => base annual price (SAR, before VAT)
 */
export const FIXED_COMPANY_PRICES = {
    1: 499,
    5: 749,
    8: 999,
    6: 1249,
    13: 1499,
    16: 1749,
    2: 1999,
    19: 2249,
    20: 2499,
    3: 2749,
    4: 2999,
    21: 3249,
    17: 3499,
};

/** Fixed surcharge for comprehensive plans (SAR) */
export const COMPREHENSIVE_FIXED_GAP = 250;

/** Deductible increase table (SAR) */
export const DEDUCTIBLE_INCREASE = {
    1000: 0,
    2000: 100,
    3000: 150,
    4000: 200,
    5000: 250,
};

/** Supported deductible options */
export const DEDUCTIBLE_OPTIONS = [1000, 2000, 3000, 4000, 5000];

/** Supported addons and prices (SAR) */
export const ADDONS_PRICES = {
    0: {
        name: 'تغطية الحوادث الشخصية للسائق',
        price: 85,
    },
    1: {
        name: 'تغطية الحوادث الشخصية للراكب',
        price: 510,
    },
};
