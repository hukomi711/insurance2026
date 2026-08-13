import { defineStore } from 'pinia';
import { ref, reactive, computed } from 'vue';

/**
 * متجر بيانات التأمين المركزي
 *
 * يجمع بيانات المركبة والسائق والوثيقة من جميع خطوات التدفق
 * في مكان واحد ويربطها بمحرك التسعير الديناميكي.
 *
 * يتزامن مع sessionStorage للتوافق مع الصفحات الحالية.
 */
export const useInsuranceStore = defineStore( 'insurance', () =>
{

    // ══════════════════════════════════════════════
    //  بيانات المركبة
    // ══════════════════════════════════════════════
    const vehicle = reactive( {
        make: '',   // معرف الشركة المصنعة (رقم من vehicleMakes)
        makeName: '',   // اسم الشركة المصنعة بالعربي
        model: '',   // الموديل
        year: '',   // سنة التصنيع
        plateNumber: '',   // رقم اللوحة
        registrationType: 'private', // نوع التسجيل: private / transport / taxi
        estimatedValue: '',   // القيمة التقديرية (ريال)
        purposeOfUse: 'personal', // الغرض: personal / commercial / rental / rideshare / cargo / petroleum
        carModification: 'no', // هل يوجد تعديلات: yes / no
        modification: '',   // وصف التعديلات
        hasTrailer: 'no', // هل يوجد مقطورة: yes / no
        trailerValue: '',   // قيمة المقطورة (ريال)
        transmissionType: '',   // ناقل الحركة: 1 (أوتوماتيك) / 2 (يدوي)
        sequenceNumber: '',   // رقم تسلسل المركبة
    } );

    // ══════════════════════════════════════════════
    //  بيانات السائق
    // ══════════════════════════════════════════════
    const driver = reactive( {
        nationalId: '',   // رقم الهوية الوطنية
        fullName: '',   // الاسم الكامل
        dateOfBirth: '',   // تاريخ الميلاد
        region: '',   // منطقة السكن
        city: '',   // المدينة
        phone: '',   // رقم الجوال
        email: '',   // البريد الإلكتروني
        drivingExperience: '',   // خبرة القيادة: 1-5
        education: '',   // المستوى التعليمي: 1-7
        accidentCounts: '',   // عدد الحوادث: 0-5
        trafficViolations: 'no', // مخالفات مرورية: yes / no
        healthConditions: 'no', // حالات صحية: yes / no
        foreignLicense: 'no', // رخصة أجنبية: yes / no
        nightParking: '',   // ركن ليلي: 1 (شارع) / 2 (ممر) / 3 (مرآب)
        expectedKM: '',   // المسافة السنوية المتوقعة: 1-5
        childrenUnder16: '',   // أطفال أقل من 16
        workLocation: '',   // مكان العمل
    } );

    /** قائمة السائقين الإضافيين */
    const additionalDrivers = ref( [] );

    // ══════════════════════════════════════════════
    //  بيانات الوثيقة
    // ══════════════════════════════════════════════
    const policy = reactive( {
        policyStartDate: '',         // تاريخ بدء الوثيقة
        insuranceType: 'tpl',      // نوع التأمين: tpl / comp
        repairMethod: 'workshop', // طريقة الإصلاح: workshop / authorized / agency
        coverageType: '',         // نوع التغطية: thirdParty / comprehensive
        deductible: '1000',     // قيمة التحمل
        addons: [],         // الإضافات المختارة
        coverageLimit: 55667,      // حد التغطية
    } );

    // ══════════════════════════════════════════════
    //  الأسعار المحسوبة (من محرك التسعير)
    // ══════════════════════════════════════════════
    const calculatedQuotes = ref( [] );

    // ══════════════════════════════════════════════
    //  الخطة المختارة (Compare → OrderReview → Checkout)
    // ══════════════════════════════════════════════
    const selectedPlan = ref( null );

    // ══════════════════════════════════════════════
    //  Getters
    // ══════════════════════════════════════════════

    /** هل تم إكمال بيانات المركبة؟ */
    const isVehicleComplete = computed( () =>
        !!vehicle.make && !!vehicle.year
    );

    /** هل تم إكمال بيانات السائق؟ */
    const isDriverComplete = computed( () =>
        !!driver.nationalId && !!driver.fullName
    );

    /** هل تم إكمال بيانات الوثيقة؟ */
    const isPolicyComplete = computed( () =>
        !!policy.insuranceType
    );

    /** جمع كل البيانات في كائن واحد */
    const allFormData = computed( () => ( {
        vehicle: { ...vehicle },
        driver: { ...driver, additionalDrivers: [ ...additionalDrivers.value ] },
        policy: { ...policy, addons: [ ...policy.addons ] },
    } ) );

    // ══════════════════════════════════════════════
    //  Actions
    // ══════════════════════════════════════════════

    /**
     * تحديث بيانات المركبة
     * @param {Object} data — الحقول المراد تحديثها
     */
    function setVehicleData ( data )
    {
        Object.keys( data ).forEach( key =>
        {
            if ( key in vehicle ) vehicle[ key ] = data[ key ];
        } );
        persistToSession();
    }

    /**
     * تحديث بيانات السائق
     * @param {Object} data — الحقول المراد تحديثها
     */
    function setDriverData ( data )
    {
        const { drivers, additionalDrivers: extraDrivers, ...rest } = data;
        Object.keys( rest ).forEach( key =>
        {
            if ( key in driver ) driver[ key ] = rest[ key ];
        } );
        // السائقون الإضافيون
        if ( Array.isArray( drivers ) )
        {
            additionalDrivers.value = drivers.filter( d => !d.isPolicyHolder );
        } else if ( Array.isArray( extraDrivers ) )
        {
            additionalDrivers.value = extraDrivers;
        }
        persistToSession();
    }

    /**
     * تحديث بيانات الوثيقة
     * @param {Object} data — الحقول المراد تحديثها
     */
    function setPolicyData ( data )
    {
        Object.keys( data ).forEach( key =>
        {
            if ( key in policy ) policy[ key ] = data[ key ];
        } );
        persistToSession();
    }

    /**
     * تحديث الأسعار المحسوبة
     * @param {Array} quotes — مصفوفة الخطط مع الأسعار المحسوبة
     */
    function setCalculatedQuotes ( quotes )
    {
        calculatedQuotes.value = quotes;
    }

    /**
     * تعيين الخطة المختارة في التدفق
     * @param {Object|null} plan
     */
    function setSelectedPlan ( plan )
    {
        selectedPlan.value = plan ? { ...plan } : null;
    }

    /**
     * مسح الخطة المختارة
     */
    function clearSelectedPlan ()
    {
        selectedPlan.value = null;
    }

    /**
     * إعادة تعيين جميع البيانات
     */
    function resetAll ()
    {
        // مركبة
        Object.assign( vehicle, {
            make: '', makeName: '', model: '', year: '', plateNumber: '',
            registrationType: 'private', estimatedValue: '', purposeOfUse: 'personal',
            carModification: 'no', modification: '', hasTrailer: 'no', trailerValue: '',
            transmissionType: '', sequenceNumber: '',
        } );
        // سائق
        Object.assign( driver, {
            nationalId: '', fullName: '', dateOfBirth: '', city: '', phone: '', email: '',
            drivingExperience: '', education: '', accidentCounts: '', trafficViolations: 'no',
            healthConditions: 'no', foreignLicense: 'no', nightParking: '', expectedKM: '',
            childrenUnder16: '', workLocation: '',
        } );
        additionalDrivers.value = [];
        // سائق
        Object.assign( driver, {
            region: '', city: '',
        } );
        // وثيقة
        Object.assign( policy, {
            policyStartDate: '', insuranceType: 'tpl', repairMethod: 'workshop',
            coverageType: '', deductible: '1000', addons: [], coverageLimit: 55667,
        } );
        calculatedQuotes.value = [];
        selectedPlan.value = null;
        // تنظيف sessionStorage
        [ 'vehicleForm', 'vehicleDetails', 'insuranceStoreData' ].forEach( k =>
            sessionStorage.removeItem( k )
        );
    }

    /**
     * استعادة البيانات من sessionStorage
     * يقرأ من المفاتيح الحالية (vehicleForm, vehicleDetails)
     * للتوافق مع الصفحات التي لم تُحدَّث بعد
     */
    function hydrateFromSession ()
    {
        // 0. بيانات basicDetails (من BasicDetailsPage)
        const basicDetails = sessionStorage.getItem( 'basicDetails' );
        if ( basicDetails )
        {
            try
            {
                const p = JSON.parse( basicDetails );
                if ( p.identityNumber ) driver.nationalId = p.identityNumber;
                if ( p.sequenceNumber ) vehicle.sequenceNumber = p.sequenceNumber;
                if ( p.city ) driver.city = p.city;
            } catch { /* ignore */ }
        }

        // 0b. بيانات ownershipTransferDetails (من OwnershipTransferPage)
        const ownershipDetails = sessionStorage.getItem( 'ownershipTransferDetails' );
        if ( ownershipDetails )
        {
            try
            {
                const p = JSON.parse( ownershipDetails );
                if ( p.identityNumber ) driver.nationalId = p.identityNumber;
                if ( p.sequenceNumber ) vehicle.sequenceNumber = p.sequenceNumber;
                if ( p.birthMonth && p.birthYear )
                {
                    driver.dateOfBirth = p.birthMonth + ' / ' + p.birthYear;
                }
                if ( p.manufacturingYear ) vehicle.year = p.manufacturingYear;
            } catch { /* ignore */ }
        }

        // 0c. بيانات importedCarDetails (من ImportedCarPage)
        const importedDetails = sessionStorage.getItem( 'importedCarDetails' );
        if ( importedDetails )
        {
            try
            {
                const p = JSON.parse( importedDetails );
                if ( p.identityNumber ) driver.nationalId = p.identityNumber;
                if ( p.customsCardNumber ) vehicle.sequenceNumber = p.customsCardNumber;
                if ( p.birthMonth && p.birthYear )
                {
                    driver.dateOfBirth = p.birthMonth + ' / ' + p.birthYear;
                }
                if ( p.manufacturingYear ) vehicle.year = p.manufacturingYear;
            } catch { /* ignore */ }
        }

        // 1. بيانات vehicleForm (من VehiclePage)
        const vehicleForm = sessionStorage.getItem( 'vehicleForm' );
        if ( vehicleForm )
        {
            try
            {
                const p = JSON.parse( vehicleForm );
                if ( p.vehicleMake ) vehicle.make = p.vehicleMake;
                if ( p.vehicleMakeName ) vehicle.makeName = p.vehicleMakeName;
                if ( p.vehicleModel ) vehicle.model = p.vehicleModel;
                if ( p.vehicleYear ) vehicle.year = p.vehicleYear;
                if ( p.plateNumber ) vehicle.plateNumber = p.plateNumber;
                if ( p.registrationType ) vehicle.registrationType = p.registrationType;
                if ( p.nationalId ) driver.nationalId = p.nationalId;
                if ( p.fullName ) driver.fullName = p.fullName;
                if ( p.dateOfBirth ) driver.dateOfBirth = p.dateOfBirth;
                if ( p.city ) driver.city = p.city;
                if ( p.phone ) driver.phone = p.phone;
                if ( p.drivingExperience ) driver.drivingExperience = p.drivingExperience;
                if ( p.coverageType ) policy.coverageType = p.coverageType;
                if ( p.deductible ) policy.deductible = p.deductible;
                if ( p.addons ) policy.addons = p.addons;
            } catch { /* ignore */ }
        }

        // 2. بيانات vehicleDetails (من VehicleDetailsPage)
        const vehicleDetails = sessionStorage.getItem( 'vehicleDetails' );
        if ( vehicleDetails )
        {
            try
            {
                const p = JSON.parse( vehicleDetails );
                if ( p.purposeOfUse ) vehicle.purposeOfUse = p.purposeOfUse;
                if ( p.estimatedValue ) vehicle.estimatedValue = p.estimatedValue;
                if ( p.sequenceNumber ) vehicle.sequenceNumber = p.sequenceNumber;
                if ( p.fullName ) driver.fullName = p.fullName;
                if ( p.phone ) driver.phone = p.phone;
                if ( p.email ) driver.email = p.email;
                if ( p.nationalId ) driver.nationalId = p.nationalId;
                // otherDetails
                if ( p.otherDetails )
                {
                    const o = p.otherDetails;
                    if ( o.nightParking ) driver.nightParking = o.nightParking;
                    if ( o.expectedKM ) driver.expectedKM = o.expectedKM;
                    if ( o.transmissionType ) vehicle.transmissionType = o.transmissionType;
                    if ( o.accidentCounts ) driver.accidentCounts = o.accidentCounts;
                    if ( o.education ) driver.education = o.education;
                    if ( o.workNameAndLocation ) driver.workLocation = o.workNameAndLocation;
                    if ( o.childrenUnder16 ) driver.childrenUnder16 = o.childrenUnder16;
                    if ( o.carModification ) vehicle.carModification = o.carModification;
                    if ( o.modification ) vehicle.modification = o.modification;
                    if ( o.hasTrailAttach ) vehicle.hasTrailer = o.hasTrailAttach;
                    if ( o.trailEstimatedValue ) vehicle.trailerValue = o.trailEstimatedValue;
                    if ( o.foreignLicense ) driver.foreignLicense = o.foreignLicense;
                    if ( o.healthConditions ) driver.healthConditions = o.healthConditions;
                    if ( o.trafficViolations ) driver.trafficViolations = o.trafficViolations;
                }
                // region و city
                if ( p.region ) driver.region = p.region;
                if ( p.city ) driver.city = p.city;
                // بيانات السياسة
                if ( p.policyStartDate ) policy.policyStartDate = p.policyStartDate;
                if ( p.insuranceType ) policy.insuranceType = p.insuranceType;
                if ( p.repairMethod ) policy.repairMethod = p.repairMethod;
                // سائقون إضافيون
                if ( Array.isArray( p.drivers ) )
                {
                    additionalDrivers.value = p.drivers.filter( d => !d.isPolicyHolder );
                }
            } catch { /* ignore */ }
        }

        // ── مزامنة coverageType ↔ insuranceType ──
        const coverageToInsurance = { thirdParty: 'tpl', comprehensive: 'comp' };
        const insuranceToCoverage = { tpl: 'thirdParty', comp: 'comprehensive' };
        if ( policy.coverageType && !policy.insuranceType )
        {
            policy.insuranceType = coverageToInsurance[ policy.coverageType ] || 'tpl';
        } else if ( policy.insuranceType && !policy.coverageType )
        {
            policy.coverageType = insuranceToCoverage[ policy.insuranceType ] || 'thirdParty';
        } else if ( policy.coverageType && policy.insuranceType )
        {
            // تأكد من التوافق — الأولوية لـ coverageType لأنها من اختيار المستخدم
            policy.insuranceType = coverageToInsurance[ policy.coverageType ] || policy.insuranceType;
        }

        // 4. محاولة استرجاع من مفتاح المتجر الموحد
        const storeData = sessionStorage.getItem( 'insuranceStoreData' );
        if ( storeData )
        {
            try
            {
                const p = JSON.parse( storeData );
                if ( p.vehicle ) Object.assign( vehicle, p.vehicle );
                if ( p.driver )
                {
                    const { additionalDrivers: ad, ...driverFields } = p.driver;
                    Object.assign( driver, driverFields );
                    if ( Array.isArray( ad ) ) additionalDrivers.value = ad;
                }
                if ( p.policy ) Object.assign( policy, p.policy );
            } catch { /* ignore */ }
        }
    }

    /**
     * حفظ البيانات إلى sessionStorage
     * يكتب بالمفتاح الموحد + المفاتيح القديمة للتوافق
     */
    function persistToSession ()
    {
        // المفتاح الموحد
        sessionStorage.setItem( 'insuranceStoreData', JSON.stringify( {
            vehicle: { ...vehicle },
            driver: { ...driver, additionalDrivers: [ ...additionalDrivers.value ] },
            policy: { ...policy, addons: [ ...policy.addons ] },
        } ) );

        // التوافق: vehicleForm
        sessionStorage.setItem( 'vehicleForm', JSON.stringify( {
            vehicleMake: vehicle.make,
            vehicleMakeName: vehicle.makeName,
            vehicleModel: vehicle.model,
            vehicleYear: vehicle.year,
            plateNumber: vehicle.plateNumber,
            registrationType: vehicle.registrationType,
            nationalId: driver.nationalId,
            fullName: driver.fullName,
            dateOfBirth: driver.dateOfBirth,
            city: driver.city,
            phone: driver.phone,
            drivingExperience: driver.drivingExperience,
            coverageType: policy.coverageType,
            deductible: policy.deductible,
            addons: policy.addons,
        } ) );

        // التوافق: vehicleDetails
        sessionStorage.setItem( 'vehicleDetails', JSON.stringify( {
            purposeOfUse: vehicle.purposeOfUse,
            estimatedValue: vehicle.estimatedValue,
            sequenceNumber: vehicle.sequenceNumber,
            nationalId: driver.nationalId,
            fullName: driver.fullName,
            phone: driver.phone,
            email: driver.email,
            vehicleMake: vehicle.make,
            vehicleYear: vehicle.year,
            region: driver.region,
            city: driver.city,
            policyStartDate: policy.policyStartDate,
            insuranceType: policy.insuranceType,
            repairMethod: policy.repairMethod,
            drivers: [
                {
                    id: 1,
                    name: driver.fullName || 'مالك الوثيقة',
                    nationalId: driver.nationalId,
                    birthDateH: '',
                    education: driver.education,
                    drivingPercentage: '100',
                    isPolicyHolder: true,
                },
                ...additionalDrivers.value,
            ],
            otherDetails: {
                nightParking: driver.nightParking,
                expectedKM: driver.expectedKM,
                transmissionType: vehicle.transmissionType,
                accidentCounts: driver.accidentCounts,
                education: driver.education,
                workNameAndLocation: driver.workLocation,
                childrenUnder16: driver.childrenUnder16,
                carModification: vehicle.carModification,
                modification: vehicle.modification,
                hasTrailAttach: vehicle.hasTrailer,
                trailEstimatedValue: vehicle.trailerValue,
                foreignLicense: driver.foreignLicense,
                healthConditions: driver.healthConditions,
                trafficViolations: driver.trafficViolations,
            },
        } ) );

        // التوافق: جميع البيانات متضمنة الآن في vehicleDetails
        // sessionStorage.setItem( 'policyDetails', JSON.stringify( {
        //     policyStartDate: policy.policyStartDate,
        //     insuranceType: policy.insuranceType,
        //     repairMethod: policy.repairMethod,
        // } ) );
    }

    return {
        // State
        vehicle,
        driver,
        additionalDrivers,
        policy,
        calculatedQuotes,
        selectedPlan,
        // Getters
        isVehicleComplete,
        isDriverComplete,
        isPolicyComplete,
        allFormData,
        // Actions
        setVehicleData,
        setDriverData,
        setPolicyData,
        setCalculatedQuotes,
        setSelectedPlan,
        clearSelectedPlan,
        resetAll,
        hydrateFromSession,
        persistToSession,
    };
} );
