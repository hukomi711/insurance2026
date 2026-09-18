import { calculateVAT, calculateMonthlyPrice } from '@/utils/pricing';
import logger from '@/utils/logger';
import {
    FIXED_COMPANY_PRICES,
    DEDUCTIBLE_INCREASE,
    ADDONS_PRICES,
} from '@/data/pricingConstants';

/**
 * محرك التسعير المبسط (نموذج ثابت 100%)
 * السيناريو الجديد: finalPrice = companyBasePrice + deductibleIncrease + addonsPrice
 */
export function usePricingEngine() {
    /**
     * حساب السعر لخطة واحدة
     */
    function calculatePremium(plan, formData = {}, overrides = {}) {
        try {
            // 1. السعر الأساسي من الشركة
            const companyId = plan.companyId || 1;
            const basePrice = FIXED_COMPANY_PRICES[companyId] || 499;

            // 2. زيادة التحمل
            const deductible = overrides.deductible || plan.deductible || 1000;
            const deductibleIncrease = DEDUCTIBLE_INCREASE[deductible] || 0;

            // 3. أسعار الإضافات
            let addonsTotal = 0;
            if (overrides.additionalCoverages && Array.isArray(overrides.additionalCoverages)) {
                addonsTotal = overrides.additionalCoverages.reduce((sum, id) => {
                    return sum + (ADDONS_PRICES[id]?.price || 0);
                }, 0);
            }

            // 4. السعر النهائي
            const annualPrice = Math.round(basePrice + deductibleIncrease + addonsTotal);
            const monthlyPrice = Math.round(annualPrice / 12);
            const vatAmount = Math.round(annualPrice * 0.15);
            const totalWithVAT = annualPrice + vatAmount;

            return {
                annualPrice,
                originalPrice: annualPrice,
                monthlyPrice,
                vatAmount,
                totalWithVAT,
                basePrice,
                pricingFactors: {
                    base: basePrice,
                    deductibleIncrease,
                    addonsTotal,
                    total: 1.0,
                },
            };
        } catch (error) {
            logger.error('[PricingEngine] calculatePremium failed:', error);
            const fallback = 499;
            return {
                annualPrice: fallback,
                originalPrice: fallback,
                monthlyPrice: Math.round(fallback / 12),
                vatAmount: Math.round(fallback * 0.15),
                totalWithVAT: fallback + Math.round(fallback * 0.15),
                basePrice: fallback,
                pricingFactors: { base: fallback, deductibleIncrease: 0, addonsTotal: 0, total: 1.0 },
            };
        }
    }

    /**
     * حساب الأسعار لجميع الخطط
     */
    function calculateAllQuotes(plans, formData = {}, overrides = {}) {
        return plans.map(plan => {
            const pricing = calculatePremium(plan, formData, overrides);
            return { ...plan, ...pricing };
        });
    }

    return {
        calculatePremium,
        calculateAllQuotes,
    };
}
