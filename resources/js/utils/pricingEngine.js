import { calculateVAT, calculateMonthlyPrice } from '@/utils/pricing';
import logger from '@/utils/logger';
import
{
    BASE_PREMIUMS,
    PRICE_LIMITS,
    VEHICLE_AGE_FACTORS,
    VEHICLE_VALUE_FACTORS,
    PURPOSE_FACTORS,
    MODIFICATION_FACTOR,
    TRAILER_FACTOR,
    TRANSMISSION_FACTORS,
    DRIVER_AGE_FACTORS,
    ACCIDENT_FACTORS,
    VIOLATION_FACTORS,
    EDUCATION_FACTORS,
    FOREIGN_LICENSE_FACTOR,
    HEALTH_CONDITION_FACTOR,
    ADDITIONAL_DRIVER_FACTOR,
    EXPERIENCE_FACTORS,
    CITY_FACTORS,
    PARKING_FACTORS,
    MILEAGE_FACTORS,
    DEDUCTIBLE_FACTORS,
    REPAIR_METHOD_FACTORS,
    COVERAGE_LIMIT_FACTORS,
    COMPANY_PRICING_FACTORS,
    SUBTYPE_COMPANY_PRICING_FACTORS,
    NCD_FACTORS,
    PROMOTIONAL_DISCOUNT_FACTOR,
} from '@/data/pricingConstants';

/**
 * محرك التسعير الديناميكي
 *
 * يحول الأسعار الثابتة إلى أسعار ديناميكية بناءً على:
 * - بيانات المركبة (العمر، النوع، القيمة، الغرض)
 * - بيانات السائق (العمر، الخبرة، الحوادث، التعليم)
 * - بيانات الموقع (المدينة، الركن الليلي، المسافة)
 * - بيانات الوثيقة (الخصم، طريقة الإصلاح)
 * - معامل الشركة الخاص
 *
 * الاستخدام:
 *   const { calculateAllQuotes, calculatePremium, getPricingBreakdown } = usePricingEngine();
 */
export function usePricingEngine ()
{

    // ═══════════════════════════════════════════════
    //  حساب معاملات الخطر الفردية
    // ═══════════════════════════════════════════════

    /**
     * معامل عمر المركبة
     */
    function getVehicleAgeFactor ( vehicleYear )
    {
        if ( !vehicleYear ) return 1.0;
        const currentYear = new Date().getFullYear();
        const age = currentYear - Number( vehicleYear );
        const entry = VEHICLE_AGE_FACTORS.find( e => age <= e.maxAge );
        return entry ? entry.factor : 1.0;
    }

    /**
     * معامل الشركة المصنعة
     */
    function getManufacturerFactor ( _makeId )
    {
        return 1.0;
    }

    /**
     * معامل قيمة المركبة
     */
    function getVehicleValueFactor ( estimatedValue )
    {
        if ( !estimatedValue ) return 1.0;
        const val = Number( estimatedValue );
        const entry = VEHICLE_VALUE_FACTORS.find( e => val <= e.maxValue );
        return entry ? entry.factor : 1.0;
    }

    /**
     * معامل الغرض من الاستخدام
     */
    function getPurposeFactor ( purpose )
    {
        return PURPOSE_FACTORS[ purpose ] || 1.0;
    }

    /**
     * معامل تعديلات المركبة
     */
    function getModificationFactor ( hasModification )
    {
        return hasModification === 'yes' ? MODIFICATION_FACTOR : 1.0;
    }

    /**
     * معامل المقطورة
     */
    function getTrailerFactor ( hasTrailer )
    {
        return hasTrailer === 'yes' ? TRAILER_FACTOR : 1.0;
    }

    /**
     * معامل ناقل الحركة
     */
    function getTransmissionFactor ( type )
    {
        if ( type === undefined || type === null || type === '' ) return 1.0;
        return TRANSMISSION_FACTORS[ String( type ) ] ?? 1.0;
    }

    /**
     * معامل جميع عوامل المركبة مجتمعة
     */
    function getVehicleRiskFactor ( vehicleData )
    {
        return (
            getVehicleAgeFactor( vehicleData.year ) *
            getManufacturerFactor( vehicleData.make ) *
            getVehicleValueFactor( vehicleData.estimatedValue ) *
            getPurposeFactor( vehicleData.purposeOfUse ) *
            getModificationFactor( vehicleData.carModification ) *
            getTrailerFactor( vehicleData.hasTrailer ) *
            getTransmissionFactor( vehicleData.transmissionType )
        );
    }

    /**
     * معامل عمر السائق
     */
    function getDriverAgeFactor ( dateOfBirth )
    {
        if ( !dateOfBirth ) return 1.0;
        const today = new Date();
        const dob = new Date( dateOfBirth );
        if ( isNaN( dob.getTime() ) ) return 1.0;
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if ( monthDiff < 0 || ( monthDiff === 0 && today.getDate() < dob.getDate() ) )
        {
            age--;
        }
        const entry = DRIVER_AGE_FACTORS.find( e => age <= e.maxAge );
        return entry ? entry.factor : 1.0;
    }

    /**
     * معامل خبرة القيادة
     */
    function getExperienceFactor ( experience )
    {
        if ( experience === undefined || experience === null || experience === '' ) return 1.0;
        return EXPERIENCE_FACTORS[ String( experience ) ] ?? 1.0;
    }

    /**
     * معامل الحوادث
     */
    function getAccidentFactor ( accidentCount )
    {
        return ACCIDENT_FACTORS[ accidentCount ] || 1.0;
    }

    /**
     * معامل المخالفات
     */
    function getViolationFactor ( violations )
    {
        return VIOLATION_FACTORS[ violations ] || 1.0;
    }

    /**
     * معامل التعليم
     */
    function getEducationFactor ( education )
    {
        return EDUCATION_FACTORS[ education ] || 1.0;
    }

    /**
     * معامل السائقين الإضافيين
     */
    function getAdditionalDriversFactor ( drivers )
    {
        if ( !Array.isArray( drivers ) || drivers.length === 0 ) return 1.0;
        // كل سائق إضافي يزيد المعامل بـ 5%
        return Math.pow( ADDITIONAL_DRIVER_FACTOR, drivers.length );
    }

    /**
     * معامل جميع عوامل السائق مجتمعة
     */
    function getDriverRiskFactor ( driverData )
    {
        return (
            getDriverAgeFactor( driverData.dateOfBirth ) *
            getExperienceFactor( driverData.drivingExperience ) *
            getAccidentFactor( driverData.accidentCounts ) *
            getViolationFactor( driverData.trafficViolations ) *
            getEducationFactor( driverData.education ) *
            ( driverData.foreignLicense === 'yes' ? FOREIGN_LICENSE_FACTOR : 1.0 ) *
            ( driverData.healthConditions === 'yes' ? HEALTH_CONDITION_FACTOR : 1.0 ) *
            getAdditionalDriversFactor( driverData.additionalDrivers )
        );
    }

    /**
     * معامل المدينة
     */
    function getCityFactor ( city )
    {
        if ( !city ) return 1.0;
        return CITY_FACTORS[ city ] || CITY_FACTORS._default;
    }

    /**
     * معامل الركن الليلي
     */
    function getParkingFactor ( parking )
    {
        return PARKING_FACTORS[ parking ] || 1.0;
    }

    /**
     * معامل المسافة السنوية
     */
    function getMileageFactor ( mileage )
    {
        return MILEAGE_FACTORS[ mileage ] || 1.0;
    }

    /**
     * معامل جميع عوامل الموقع والأسلوب مجتمعة
     */
    function getLifestyleRiskFactor ( driverData )
    {
        return (
            getCityFactor( driverData.city ) *
            getParkingFactor( driverData.nightParking ) *
            getMileageFactor( driverData.expectedKM )
        );
    }

    /**
     * معامل الخصم/التحمل
     */
    function getDeductibleFactor ( deductible )
    {
        if ( deductible === undefined || deductible === null || deductible === '' ) return 1.0;
        const val = Number( deductible );
        // ابحث عن أقرب قيمة
        if ( DEDUCTIBLE_FACTORS[ val ] !== undefined ) return DEDUCTIBLE_FACTORS[ val ];
        // اقرب قيمة
        const keys = Object.keys( DEDUCTIBLE_FACTORS ).map( Number ).sort( ( a, b ) => a - b );
        const closest = keys.reduce( ( prev, curr ) =>
            Math.abs( curr - val ) < Math.abs( prev - val ) ? curr : prev
        );
        return DEDUCTIBLE_FACTORS[ closest ] || 1.0;
    }

    /**
     * معامل طريقة الإصلاح
     */
    function getRepairMethodFactor ( method )
    {
        return REPAIR_METHOD_FACTORS[ method ] || 1.0;
    }

    /**
     * معامل حد التغطية (يؤثر على الشامل وأضرار المركبة بلس وضد الغير بلس)
     */
    function getCoverageLimitFactor ( coverageLimit, planType )
    {
        const affectedTypes = [ 'comprehensive', 'vehicleDamagePlus', 'thirdPartyPlus' ];
        if ( !affectedTypes.includes( planType ) || !coverageLimit ) return 1.0;
        const val = Number( coverageLimit );
        const entry = COVERAGE_LIMIT_FACTORS.find( e => val <= e.maxValue );
        return entry ? entry.factor : 1.0;
    }

    /**
     * معامل خصم عدم وجود مطالبات (NCD)
     * @param {number|string} ncdYears - عدد سنوات بدون مطالبات
     * @returns {number}
     */
    function getNcdFactor ( ncdYears )
    {
        if ( ncdYears === undefined || ncdYears === null || ncdYears === '' ) return 1.0;
        return NCD_FACTORS[ String( ncdYears ) ] || 1.0;
    }

    /**
     * معامل جميع عوامل الوثيقة مجتمعة
     */
    function getPolicyFactor ( policyData, planDeductible )
    {
        // استخدم خصم الخطة إذا لم يُحدد خصم من المستخدم
        const deductible = planDeductible !== undefined ? planDeductible : policyData.deductible;
        return (
            getDeductibleFactor( deductible ) *
            getRepairMethodFactor( policyData.repairMethod )
        );
    }

    // ═══════════════════════════════════════════════
    //  حساب السعر النهائي
    // ═══════════════════════════════════════════════

    /**
     * حساب السعر السنوي لخطة واحدة
     *
     * @param {Object} plan - الخطة الأساسية (من plans.js)
     * @param {Object} formData - بيانات النموذج { vehicle, driver, policy }
     * @param {Object} [overrides] - تجاوزات (مثل تغيير الخصم أو طريقة الإصلاح في ComparePage)
     * @returns {Object} { annualPrice, monthlyPrice, vatAmount, totalWithVAT, factors }
     */
    function calculatePremium ( plan, formData, overrides = {} )
    {
        try
        {
            const { vehicle: v, driver: d, policy: p } = formData;

            // السعر الأساسي من خريطة الفئات
            const basePrice = BASE_PREMIUMS[ plan.subType ] ?? 800;

            // حساب المعاملات
            const vehicleFactor = getVehicleRiskFactor( v || {} );
            const driverFactor = getDriverRiskFactor( d || {} );
            const lifestyleFactor = getLifestyleRiskFactor( d || {} );

            // الخصم وطريقة الإصلاح — يمكن تجاوزها
            const effectivePolicy = {
                ...( p || {} ),
                ...overrides,
            };
            const effectiveDeductible = overrides.deductible
                ?? effectivePolicy.deductible
                ?? plan.deductible;
            const policyFactor = getPolicyFactor( effectivePolicy, effectiveDeductible );

            // معامل الشركة
            const companyFactor = SUBTYPE_COMPANY_PRICING_FACTORS[ plan.subType ]?.[ plan.companyId ]
                ?? COMPANY_PRICING_FACTORS[ plan.companyId ]
                ?? 1.0;

            // معامل NCD (خصم عدم وجود مطالبات)
            const ncdFactor = getNcdFactor( d?.ncdYears );

            // معامل حد التغطية
            const coverageFactor = getCoverageLimitFactor( overrides.coverageLimit ?? effectivePolicy.coverageLimit, plan.subType );

            // السعر النهائي
            const rawPrice = basePrice * vehicleFactor * driverFactor * lifestyleFactor * policyFactor * companyFactor * ncdFactor * coverageFactor;

            // تطبيق حدود الأسعار
            const limits = PRICE_LIMITS[ plan.subType ] || { min: 500, max: 8000 };
            const clampedPrice = Math.max( limits.min, Math.min( limits.max, rawPrice ) );

            // تقريب لأقرب 10
            const annualBeforeDiscount = Math.round( clampedPrice / 10 ) * 10;

            // الخصم الترويجي — مطابق لـ config/pricing.php
            const annualPrice = Math.round( ( annualBeforeDiscount * PROMOTIONAL_DISCOUNT_FACTOR ) / 10 ) * 10;
            const originalPrice = annualBeforeDiscount;

            const monthlyPrice = calculateMonthlyPrice( annualPrice );
            const vatAmount = calculateVAT( annualPrice );
            const totalWithVAT = annualPrice + vatAmount;

            return {
                annualPrice,
                originalPrice,
                monthlyPrice,
                vatAmount,
                totalWithVAT,
                basePrice,
                factors: {
                    vehicle: Math.round( vehicleFactor * 1000 ) / 1000,
                    driver: Math.round( driverFactor * 1000 ) / 1000,
                    lifestyle: Math.round( lifestyleFactor * 1000 ) / 1000,
                    policy: Math.round( policyFactor * 1000 ) / 1000,
                    company: companyFactor,
                    ncd: ncdFactor,
                    coverage: Math.round( coverageFactor * 1000 ) / 1000,
                    promo: PROMOTIONAL_DISCOUNT_FACTOR,
                    total: Math.round( ( vehicleFactor * driverFactor * lifestyleFactor * policyFactor * companyFactor * ncdFactor * coverageFactor ) * 1000 ) / 1000,
                },
            };
        } catch ( error )
        {
            logger.error( '[PricingEngine] calculatePremium failed:', error, { planId: plan?.id, subType: plan?.subType } );
            // Fallback: return static plan prices
            const fallbackPrice = plan?.annualPrice || plan?.basePrice || 800;
            return {
                annualPrice: fallbackPrice,
                originalPrice: fallbackPrice,
                monthlyPrice: calculateMonthlyPrice( fallbackPrice ),
                vatAmount: calculateVAT( fallbackPrice ),
                totalWithVAT: fallbackPrice + calculateVAT( fallbackPrice ),
                basePrice: fallbackPrice,
                factors: { vehicle: 1, driver: 1, lifestyle: 1, policy: 1, company: 1, ncd: 1, coverage: 1, promo: PROMOTIONAL_DISCOUNT_FACTOR, total: 1 },
            };
        }
    }

    /**
     * حساب الأسعار لجميع الخطط
     *
     * @param {Array} plans - مصفوفة الخطط الأساسية
     * @param {Object} formData - بيانات النموذج { vehicle, driver, policy }
     * @param {Object} [overrides] - تجاوزات عامة
     * @returns {Array} مصفوفة خطط مع أسعار محدّثة
     */
    function calculateAllQuotes ( plans, formData, overrides = {} )
    {
        return plans.map( plan =>
        {
            const pricing = calculatePremium( plan, formData, overrides );
            return {
                ...plan,
                annualPrice: pricing.annualPrice,
                originalPrice: pricing.originalPrice,
                monthlyPrice: pricing.monthlyPrice,
                basePrice: pricing.basePrice,
                pricingFactors: pricing.factors,
            };
        } );
    }

    /**
     * الحصول على تفاصيل معاملات الخطر بالعربي (لعرضها في واجهة المستخدم)
     *
     * @param {Object} plan - خطة مع pricingFactors
     * @param {Object} formData - بيانات النموذج
     * @returns {Array<{ label: string, factor: number, impact: string }>}
     */
    function getPricingBreakdown ( plan, formData )
    {
        const { vehicle: v, driver: d } = formData;
        const items = [];

        // المركبة
        const ageFactor = getVehicleAgeFactor( v?.year );
        if ( ageFactor !== 1.0 )
        {
            items.push( {
                label: 'عمر المركبة',
                factor: ageFactor,
                impact: ageFactor < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( ageFactor - 1 ) * 100 ),
            } );
        }

        const mfgFactor = getManufacturerFactor( v?.make );
        if ( mfgFactor !== 1.0 )
        {
            items.push( {
                label: 'الشركة المصنعة',
                factor: mfgFactor,
                impact: mfgFactor < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( mfgFactor - 1 ) * 100 ),
            } );
        }

        const valFactor = getVehicleValueFactor( v?.estimatedValue );
        if ( valFactor !== 1.0 )
        {
            items.push( {
                label: 'قيمة المركبة',
                factor: valFactor,
                impact: valFactor < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( valFactor - 1 ) * 100 ),
            } );
        }

        // السائق
        const expFactor = getExperienceFactor( d?.drivingExperience );
        if ( expFactor !== 1.0 )
        {
            items.push( {
                label: 'خبرة القيادة',
                factor: expFactor,
                impact: expFactor < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( expFactor - 1 ) * 100 ),
            } );
        }

        const accFactor = getAccidentFactor( d?.accidentCounts );
        if ( accFactor !== 1.0 )
        {
            items.push( {
                label: 'سجل الحوادث',
                factor: accFactor,
                impact: accFactor < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( accFactor - 1 ) * 100 ),
            } );
        }

        const driverAge = getDriverAgeFactor( d?.dateOfBirth );
        if ( driverAge !== 1.0 )
        {
            items.push( {
                label: 'عمر السائق',
                factor: driverAge,
                impact: driverAge < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( driverAge - 1 ) * 100 ),
            } );
        }

        // الموقع
        const cityFactor = getCityFactor( d?.city );
        if ( cityFactor !== 1.0 )
        {
            items.push( {
                label: 'المدينة',
                factor: cityFactor,
                impact: cityFactor < 1 ? 'خصم' : 'زيادة',
                percent: Math.round( ( cityFactor - 1 ) * 100 ),
            } );
        }

        // NCD
        const ncdFactor = getNcdFactor( d?.ncdYears );
        if ( ncdFactor !== 1.0 )
        {
            items.push( {
                label: 'خصم عدم وجود مطالبات (NCD)',
                factor: ncdFactor,
                impact: 'خصم',
                percent: Math.round( ( ncdFactor - 1 ) * 100 ),
            } );
        }

        return items;
    }

    /**
     * إعادة حساب سعر خطة واحدة (عند تغيير الخصم في الكارت)
     *
     * @param {Object} plan - الخطة الأساسية
     * @param {Object} formData - بيانات النموذج
     * @param {number} newDeductible - قيمة الخصم الجديدة
     * @returns {Object} الخطة بسعر محدّث
     */
    function recalculateSinglePlan ( plan, formData, newDeductible )
    {
        const pricing = calculatePremium( plan, formData, { deductible: newDeductible } );
        return {
            ...plan,
            annualPrice: pricing.annualPrice,
            originalPrice: pricing.originalPrice,
            monthlyPrice: pricing.monthlyPrice,
            pricingFactors: pricing.factors,
        };
    }

    return {
        calculatePremium,
        calculateAllQuotes,
        getPricingBreakdown,
        recalculateSinglePlan,
        // تصدير المعاملات الفردية للاختبار
        getVehicleRiskFactor,
        getDriverRiskFactor,
        getLifestyleRiskFactor,
    };
}
