<template>
    <div dir="rtl">

        <!-- Tab Navigation -->
        <TabsRoot v-model="activeTab">
            <TabsList class="flex justify-center gap-1 overflow-x-auto no-scrollbar px-1">
                <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value" class="summary-tab-trigger flex-1 min-w-fit flex items-center justify-center gap-2 py-2.5 px-4 typ-s2 font-bold rounded-t-[15px] shadow-md transition-all whitespace-nowrap cursor-pointer
                           text-slate-600 bg-white
                           data-[state=active]:bg-[#0088eb] data-[state=active]:text-white">
                    <span
                        class="w-[30px] h-[30px] bg-white rounded-full inline-flex items-center justify-center shrink-0 shadow-sm overflow-hidden p-1">
                        <img :src="tab.icon" :alt="tab.label" class="w-full h-full object-contain" width="30" height="30" />
                    </span>
                    <span class="hidden sm:inline">{{ tab.label }}</span>
                </TabsTrigger>
            </TabsList>

            <!-- ═══ مالك الوثيقة ═══ -->
            <TabsContent value="policyholder"
                class="summary-tab-content p-5 focus:outline-none bg-[#f8f8f8] shadow-md rounded-b-[20px]">
                <div class="bg-white shadow-md rounded-[20px] p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                        <SummaryRow label="الاسم" :value="driverData.fullName || '—'" />
                        <SummaryRow label="رقم الهوية" :value="maskId(driverData.nationalId)" ltr />
                        <SummaryRow label="تاريخ الميلاد" :value="driverData.dateOfBirth || '—'" ltr />
                        <SummaryRow label="المدينة" :value="driverData.city || '—'" />
                        <SummaryRow label="رقم الجوال" :value="maskPhone(driverData.phone)" ltr />
                        <SummaryRow label="البريد الإلكتروني" :value="driverData.email || '—'" ltr />
                    </div>
                </div>
            </TabsContent>

            <!-- ═══ معلومات المركبة ═══ -->
            <TabsContent value="vehicle"
                class="summary-tab-content p-5 focus:outline-none bg-[#f8f8f8] shadow-md rounded-b-[20px]">
                <div class="bg-white shadow-md rounded-[20px] p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                        <SummaryRow label="الشركة المصنعة" :value="vehicleData.makeName || '—'" />
                        <SummaryRow label="سنة الصنع" :value="vehicleData.year || '—'" ltr />
                        <SummaryRow label="رقم اللوحة" :value="vehicleData.plateNumber || '—'" ltr />
                        <SummaryRow label="الرقم التسلسلي" :value="vehicleData.sequenceNumber || '—'" ltr />
                        <SummaryRow label="القيمة التقديرية" :value="formatSar(vehicleData.estimatedValue)" ltr />
                        <SummaryRow label="الغرض من الاستخدام" :value="purposeLabel" />
                        <SummaryRow label="نوع التسجيل" :value="registrationLabel" />
                        <SummaryRow label="ناقل الحركة" :value="transmissionLabel" />
                    </div>
                </div>
            </TabsContent>

            <!-- ═══ بيانات السائقين ═══ -->
            <TabsContent value="drivers"
                class="summary-tab-content p-5 focus:outline-none bg-[#f8f8f8] shadow-md rounded-b-[20px]">
                <div class="bg-white shadow-md rounded-[20px] p-5">
                    <!-- Primary driver -->
                    <div class="mb-2">
                        <span class="inline-flex items-center gap-1.5 typ-s2 font-bold text-foreground mb-2">
                            <svg class="size-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            مالك الوثيقة (السائق الرئيسي)
                        </span>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 bg-slate-50 rounded-xl p-4">
                            <SummaryRow label="المستوى التعليمي" :value="educationLabel" />
                            <SummaryRow label="عدد الحوادث (آخر 5 سنوات)" :value="driverData.accidentCounts || '0'"
                                ltr />
                            <SummaryRow label="سنوات الخبرة في القيادة" :value="experienceLabel" />
                            <SummaryRow label="المخالفات المرورية"
                                :value="driverData.trafficViolations === 'yes' ? 'نعم' : 'لا'" />
                            <SummaryRow label="رخصة قيادة أجنبية"
                                :value="driverData.foreignLicense === 'yes' ? 'نعم' : 'لا'" />
                            <SummaryRow label="حالات صحية"
                                :value="driverData.healthConditions === 'yes' ? 'نعم' : 'لا'" />
                        </div>
                    </div>

                    <!-- Additional drivers -->
                    <div v-if="additionalDriversList.length > 0" class="mt-4 space-y-3">
                        <div v-for="(drv, idx) in additionalDriversList" :key="idx">
                            <span class="inline-flex items-center gap-1.5 typ-s2 font-bold text-foreground mb-2">
                                <svg class="size-4 text-slate-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                سائق إضافي {{ idx + 1 }}
                            </span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 bg-slate-50 rounded-xl p-4">
                                <SummaryRow label="الاسم" :value="drv.name || '—'" />
                                <SummaryRow label="رقم الهوية" :value="maskId(drv.nationalId)" ltr />
                                <SummaryRow label="تاريخ الميلاد" :value="drv.dateOfBirth || '—'" ltr />
                            </div>
                        </div>
                    </div>
                    <p v-else class="typ-c1 text-muted mt-2">لا يوجد سائقين إضافيين</p>
                </div>
            </TabsContent>

            <!-- ═══ تفاصيل الوثيقة ═══ -->
            <TabsContent value="policy"
                class="summary-tab-content p-5 focus:outline-none bg-[#f8f8f8] shadow-md rounded-b-[20px]">
                <div class="bg-white shadow-md rounded-[20px] p-5">
                    <!-- Company logo centered -->
                    <div v-if="selectedCompany" class="flex justify-center py-4 mb-4">
                        <img :src="selectedCompany.logo" :alt="selectedCompany.name"
                            class="max-w-[140px] max-h-[60px] object-contain" width="140" height="60" />
                    </div>

                    <!-- تأمين مع heading + معاينة الكل -->
                    <div class="detail-box relative items-center w-full">
                        <h4 class="text-lg font-bold text-foreground">تأمين مع</h4>
                        <button class="detail-box-show p-2 cursor-pointer" :aria-expanded="showAllExpanded"
                            @click="showAllExpanded = !showAllExpanded">
                            <img :src="showAllUpIcon" class="max-w-full transition-transform"
                                :class="showAllExpanded ? '' : 'rotate-180'" loading="lazy" width="24" height="24" />
                            <span
                                class="text-inherit font-semibold">{{ showAllExpanded ? 'إغلاق' : 'معاينة الكل' }}</span>
                        </button>

                        <!-- Coverage feature list -->
                        <ul class="feature-list mt-3">
                            <li class="ps-6">المسؤولية تجاه الغير (الطرف الثالث): الحد الأقصى لمسؤولية الشركة في الواقعة
                                الواحدة وخلال فترة سريان وثيقة التأمين بالنسبة للأضرار الجسدية (بما في ذلك الديات
                                والمبالغ المقدرة عن الإصابات والمصاريف الطبية) والأضرار المادية معاً لن تتجاوز مبلغاً
                                إجمالياً قدره 10,000,000 ريال (عشرة ملايين ريال سعودي) حداً أقصى لمسئولية الشركة</li>
                            <div class="transition-all duration-300"
                                :class="showAllExpanded ? 'opacity-100 visible max-h-screen' : 'opacity-0 invisible max-h-0 overflow-hidden'">
                                <li class="ps-6">تغطية الخسارة الكلية أو الجزئية للمركبة</li>
                                <li class="ps-6">تغطية السرقة

                                    أ). السيارات المسروقـة يتم التعويض عنهـا علـى أساس الخسارة الكليـة بعد مرور 90 يوم
                                    من تاريخ الإبلاغ عن فقدانهــا مع تقديم تقرير الشرطة إلى شركة التأمين. مع العلم أن
                                    التأمين لايشمل السيارات المسروقة مالم تكن السيارات مغلقة بشكل سليم ولم يتم ترك
                                    المفتاح بداخلها أو أن يتم ترك المركبة على وضع التشغيل.

                                    ب). اذا وجدت السيارة المسروقة بعد التعويض تصبح من ممتلكات شركة التأمين، كما يستثنى
                                    من التأمين سرقة كافة الاإكسسوارات التي تمت إضافتها عل المركبة إلا في حال الإبلاغ
                                    عنها وتم إضافة قيمتها عل القيمة التأمينية للسيارة وذلك قبل إصدار الوثيقة.

                                    تغطية الحرائق

                                    يتم التعويض إذا تعرضت المركبة لحريق بسبب اشتعال أو تلامس كهربائي نتج عنه تلف كلي
                                    للمركبة</li>
                                <li class="ps-6">الشامل والمخاطر الطبيعية</li>
                                <li class="ps-6">الخسارة أو الضرر الناجم عن العواصف، البرد، المطر، الفيضانات</li>
                                <li class="ps-6">مصاريف السحب: (أجور السحب والعناية والحماية محدده بمبلغ وقدره 500 ريال
                                    سعودي داخل المدينة و بمبلغ قدره 1,000 ريال سعودي خارج المدينة في كل حادث.)</li>
                            </div>
                        </ul>
                    </div>

                    <!-- Policy dates card -->
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 mt-5">
                        <SummaryRow label="تاريخ بدء الوثيقة" :value="policyData.policyStartDate || '—'" ltr />
                        <SummaryRow label="نوع التأمين" :value="insuranceTypeLabel" />
                        <SummaryRow v-if="policyData.insuranceType === 'comp'" label="مركز الإصلاح" :value="repairMethodLabel" />
                        <SummaryRow label="تاريخ انتهاء الوثيقة" :value="policyEndDate" ltr />
                    </div>
                </div>
            </TabsContent>
        </TabsRoot>

    </div>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { TabsRoot, TabsList, TabsTrigger, TabsContent } from 'radix-vue';
import { useInsuranceStore } from '@/store';
import { formatPrice } from '@/data';

import policyholderIcon from '@/../images/icons/policyholder-detailicon.svg';
import vehicleIcon from '@/../images/icons/vehicledetail-icon.svg';
import driversIcon from '@/../images/icons/driverdetail-icon.svg';
import policyIcon from '@/../images/icons/policydetail-icon.svg';
import showAllUpIcon from '@/../images/icons/show-all-up.svg';

const props = defineProps( {
    /** Optional selected company info { name, logo } */
    selectedCompany: {
        type: Object,
        default: null,
    },
    /** Default active tab */
    defaultTab: {
        type: String,
        default: 'policy',
    },
} );

const insuranceStore = useInsuranceStore();
const activeTab = ref( props.defaultTab );
const showAllExpanded = ref( false );

// ── Store shorthands ──
const vehicleData = computed( () => insuranceStore.vehicle );
const driverData = computed( () => insuranceStore.driver );
const policyData = computed( () => insuranceStore.policy );
const additionalDriversList = computed( () => insuranceStore.additionalDrivers || [] );

// ── Label maps ──
const purposeMap = {
    personal: 'شخصي',
    commercial: 'تجاري',
    rental: 'تأجير',
    rideshare: 'نقل ركاب',
    cargo: 'نقل بضائع',
    petroleum: 'بترولي',
};
const registrationMap = {
    private: 'خاص',
    transport: 'نقل',
    taxi: 'أجرة',
};
const transmissionMap = {
    '1': 'أوتوماتيك',
    '2': 'يدوي',
};
const educationMap = {
    '1': 'ابتدائي',
    '2': 'متوسط',
    '3': 'ثانوي',
    '4': 'دبلومة',
    '5': 'بكالوريوس',
    '6': 'ماجستير',
    '7': 'دكتوراه',
};
const experienceMap = {
    '1': 'أقل من سنة',
    '2': '1-2 سنة',
    '3': '3-5 سنوات',
    '4': '6-10 سنوات',
    '5': 'أكثر من 10 سنوات',
};
const insuranceTypeMap = {
    tpl: 'ضد الغير',
    comp: 'شامل',
};
const repairMethodMap = {
    workshop: 'ورشة',
    authorized: 'الورش المعتمدة',
    agency: 'وكالة',
};

// ── Computed labels ──
const purposeLabel = computed( () => purposeMap[ vehicleData.value.purposeOfUse ] || vehicleData.value.purposeOfUse || '—' );
const registrationLabel = computed( () => registrationMap[ vehicleData.value.registrationType ] || vehicleData.value.registrationType || '—' );
const transmissionLabel = computed( () => transmissionMap[ vehicleData.value.transmissionType ] || '—' );
const educationLabel = computed( () => educationMap[ driverData.value.education ] || '—' );
const experienceLabel = computed( () => experienceMap[ driverData.value.drivingExperience ] || '—' );
const insuranceTypeLabel = computed( () => insuranceTypeMap[ policyData.value.insuranceType ] || policyData.value.insuranceType || '—' );
const repairMethodLabel = computed( () => repairMethodMap[ policyData.value.repairMethod ] || policyData.value.repairMethod || '—' );

// Policy end date = start + 1 year
const policyEndDate = computed( () => {
    const start = policyData.value.policyStartDate;
    if ( !start ) return '—';
    try {
        const parts = start.split( '/' );
        let d;
        if ( parts.length === 3 ) {
            // dd/mm/yyyy
            d = new Date( parts[ 2 ], parts[ 1 ] - 1, parts[ 0 ] );
        } else {
            d = new Date( start );
        }
        if ( isNaN( d.getTime() ) ) return '—';
        d.setFullYear( d.getFullYear() + 1 );
        d.setDate( d.getDate() - 1 );
        const dd = String( d.getDate() ).padStart( 2, '0' );
        const mm = String( d.getMonth() + 1 ).padStart( 2, '0' );
        const yyyy = d.getFullYear();
        return `${ dd }/${ mm }/${ yyyy }`;
    } catch {
        return '—';
    }
} );

// ── Helpers ──
function formatSar( val ) {
    if ( !val ) return '—';
    return formatPrice( Number( val ) );
}

function maskId( id ) {
    if ( !id ) return '—';
    const s = String( id );
    if ( s.length < 4 ) return s;
    return s.slice( 0, 2 ) + '••••••' + s.slice( -2 );
}

function maskPhone( phone ) {
    if ( !phone ) return '—';
    const s = String( phone );
    if ( s.length < 4 ) return s;
    return s.slice( 0, 4 ) + '••••' + s.slice( -2 );
}

// ── Tab definitions with SVG icons ──
const tabs = [
    {
        value: 'policyholder',
        label: 'مالك الوثيقة',
        icon: policyholderIcon,
    },
    {
        value: 'vehicle',
        label: 'معلومات المركبة',
        icon: vehicleIcon,
    },
    {
        value: 'drivers',
        label: 'بيانات السائقين',
        icon: driversIcon,
    },
    {
        value: 'policy',
        label: 'تفاصيل الوثيقة',
        icon: policyIcon,
    },
];

// ── SummaryRow sub-component ──
const SummaryRow = {
    props: {
        label: String,
        value: String,
        ltr: Boolean,
    },
    render() {
        return h( 'div', { class: 'flex items-center justify-between py-2 border-b border-dashed border-slate-100 last:border-0' }, [
            h( 'span', { class: 'typ-s2 font-bold text-foreground' }, this.label ),
            h( 'span', {
                class: [
                    'typ-s2 text-muted',
                    this.ltr ? 'ltr-nums direction-ltr' : '',
                ].filter( Boolean ).join( ' ' ),
            }, this.value || '—' ),
        ] );
    },
};
</script>

<style scoped>
.direction-ltr {
    direction: ltr;
}

.summary-tab-content {
    position: relative;
}

.summary-tab-content::after {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    height: 5px;
    background: #0088eb;
    border-radius: 0 0 4px 4px;
}

.detail-box-show {
    position: absolute;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 12px;
    top: 10px;
    inset-inline-start: 0;
    width: max-content;
    padding: 0.5rem;
    min-width: 102px;
    background-color: #f8f8f8;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.13);
    border-start-start-radius: 3px;
    border-start-end-radius: 3px;
    border-end-start-radius: 12px;
    border-end-end-radius: 12px;
    color: #0088eb;
    justify-content: space-around;
    border: 0;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    position: relative;
    font-size: 0.85rem;
    line-height: 1.7;
    color: #334155;
    padding-block: 0.5rem;
    border-bottom: 1px dashed #e2e8f0;
    white-space: pre-line;
}

.feature-list li:last-child {
    border-bottom: none;
}

.feature-list li::before {
    content: '';
    position: absolute;
    inset-inline-start: 0;
    top: 0.85rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #0088eb;
}
</style>
