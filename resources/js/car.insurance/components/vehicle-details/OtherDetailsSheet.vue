<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 bg-black/50 z-50 data-[state=open]:animate-fade-in" />
            <DialogContent
                class="fixed inset-x-0 bottom-0 z-50 h-full bg-white shadow-lg border-t gap-4 transition ease-in-out data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:duration-300 data-[state=open]:duration-500 data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom flex flex-col overflow-y-scroll overflow-x-hidden"
                tabindex="-1">

                <!-- Close button header -->
                <div class="text-center sm:text-left flex justify-end items-end">
                    <DialogClose class="flex align-middle rounded-full p-2 bg-slate-100 mt-2 mx-2 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none"
                            class="shrink-0 size-4.5 w-5 h-5 cursor-pointer text-slate-600 font-bold">
                            <path d="M5 5L15 15" stroke="currentColor" stroke-width="1.13333" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M15 5L5 15" stroke="currentColor" stroke-width="1.13333" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </DialogClose>
                </div>

                <!-- Content -->
                <div class="flex flex-col m-6 flex-1">
                    <DialogTitle class="sr-only">تفاصيل أخرى</DialogTitle>
                    <DialogDescription class="sr-only">معلومات إضافية تطلبها شركات التأمين</DialogDescription>

                    <h4>تفاصيل أخرى</h4>

                    <!-- Row 1: Night Parking, Expected KM, Transmission -->
                    <div class="flex md:flex-row flex-col gap-4 w-full mt-5">
                        <div class="w-full md:w-1/3">
                            <AppSelect id="nightParking" v-model="otherDetails.nightParking"
                                :options="nightParkingOptions" label="مكان إيقاف المركبة في الليل" name="nightParking" />
                        </div>
                        <div class="w-full md:w-1/3">
                            <AppSelect id="expectedKM" v-model="otherDetails.expectedKM"
                                :options="expectedKMOptions" label="عدد الكيلومترات السنوية" name="expectedKM" />
                        </div>
                        <div class="w-full md:w-1/3">
                            <AppSelect id="transmissionType" v-model="otherDetails.transmissionType"
                                :options="transmissionOptions" label="نوع ناقل الحركة" name="transmissionType" />
                        </div>
                    </div>

                    <!-- Row 2: Accidents, Education, Work -->
                    <div class="flex md:flex-row flex-col gap-4 w-full mt-5">
                        <div class="w-full md:w-1/3">
                            <AppSelect id="accidentCounts" v-model="otherDetails.accidentCounts"
                                :options="accidentOptions" label="عدد الحوادث" placeholder="عدد الحوادث"
                                name="accidentCounts" />
                        </div>
                        <div class="w-full md:w-1/3">
                            <AppSelect id="education" v-model="otherDetails.education" :options="educationOptions"
                                label="التعليم" name="education" />
                        </div>
                        <div class="w-full md:w-1/3">
                            <div
                                class="group transition duration-300 relative flex has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full">
                                <input id="workNameAndLocation" v-model="otherDetails.workNameAndLocation" type="text"
                                    autocomplete="organization" placeholder=" " name="workNameAndLocation"
                                    class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6" />
                                <label for="workNameAndLocation"
                                    class="transition z-10 absolute !typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                    اسم وعنوان جهة العمل
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Children under 16 -->
                    <div class="flex md:flex-row flex-col gap-4 w-full mt-5">
                        <div class="w-full md:w-1/3">
                            <AppSelect id="childrenUnder16" v-model="otherDetails.childrenUnder16"
                                :options="childrenOptions" label="عدد الأطفال دون عمر ال 16" name="childrenUnder16" />
                        </div>
                    </div>

                    <!-- Yes/No: Car Modifications -->
                    <div class="flex flex-col xl:flex-row gap-4 w-full mt-5">
                        <div class="w-full flex-row md:w-3/5">
                            <div class="flex items-center">
                                <span class="text-sm md:w-1/2">هل يوجد تعديلات على المركبة؟</span>
                                <div class="flex md:w-1/2 gap-2">
                                    <div class="px-12 rounded-lg border border-gray-200 p-4 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.carModification === 'yes' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.carModification = 'yes'">
                                        <span class="text-xs text-slate-900">نعم</span>
                                    </div>
                                    <div class="px-12 rounded-lg border border-gray-200 p-4 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.carModification === 'no' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.carModification = 'no'">
                                        <span class="text-xs text-slate-900">لا</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="otherDetails.carModification === 'yes'" class="w-full md:w-2/5">
                            <div
                                class="group transition duration-300 relative flex has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full">
                                <input id="modification" v-model="otherDetails.modification" type="text"
                                    autocomplete="off" placeholder=" " name="modification" maxlength="150"
                                    class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6" />
                                <label for="modification"
                                    class="transition z-10 absolute !typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                    ادخال التعديلات
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Yes/No: Trailer -->
                    <div class="flex flex-col xl:flex-row gap-4 w-full mt-5">
                        <div class="flex items-center md:w-3/5">
                            <span class="text-sm w-1/2">مقطورة تابعة للمركبة؟</span>
                            <div class="gap-2 flex w-1/2">
                                <div class="px-12 rounded-lg border border-gray-200 p-4 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                    :class="otherDetails.hasTrailAttach === 'yes' ? 'border-blue-600 bg-blue-100' : ''"
                                    @click="otherDetails.hasTrailAttach = 'yes'">
                                    <span class="text-xs text-slate-900">نعم</span>
                                </div>
                                <div class="px-12 rounded-lg border border-gray-200 p-4 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                    :class="otherDetails.hasTrailAttach === 'no' ? 'border-blue-600 bg-blue-100' : ''"
                                    @click="otherDetails.hasTrailAttach = 'no'">
                                    <span class="text-xs text-slate-900">لا</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="otherDetails.hasTrailAttach === 'yes'" class="w-full md:w-2/5">
                            <div
                                class="group transition duration-300 relative flex has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full">
                                <input id="trailEstimatedValue" v-model="otherDetails.trailEstimatedValue" type="text"
                                    autocomplete="off" placeholder=" " maxlength="9" name="trailEstimatedValueFormatted"
                                    class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6" />
                                <!-- SAR icon -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1124.14 1256.39"
                                    class="shrink-0 w-5 h-5 self-center text-slate-600">
                                    <path fill="currentColor"
                                        d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z" />
                                    <path fill="currentColor"
                                        d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z" />
                                </svg>
                                <label for="trailEstimatedValue"
                                    class="transition z-10 absolute !typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                    القيمة التقديرية للمقطورة
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Questions -->
                    <AccordionRoot type="multiple" class="w-full mt-4" dir="rtl">
                        <!-- 1. Foreign License -->
                        <AccordionItem value="foreignLicense" class="border-b">
                            <AccordionHeader class="flex mb-0">
                                <AccordionTrigger
                                    class="px-0 flex flex-1 items-center justify-between py-4 font-medium transition-all [&[data-state=open]>svg.chevron]:rotate-180">
                                    <div class="flex justify-between w-full">
                                        <span class="text-sm font-bold mb-2">هل لدى مالك الوثيقة رخصة قيادة صالحة من دول
                                            أخرى؟</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="shrink-0 text-white bg-green-500 rounded-full p-1 w-5 h-5 align-middle me-2">
                                            <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <svg viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg"
                                        class="chevron h-[10px] w-[10px] shrink-0 transition-transform duration-200">
                                        <path d="M1.25 1.375L6.5 6.625L11.75 1.375" stroke="currentColor"
                                            stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </AccordionTrigger>
                            </AccordionHeader>
                            <AccordionContent
                                class="overflow-hidden text-sm transition-all data-[state=closed]:animate-collapse-up data-[state=open]:animate-collapse-down">
                                <div class="pb-4 flex gap-2">
                                    <div class="px-8 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.foreignLicense === 'yes' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.foreignLicense = 'yes'">
                                        <span class="text-xs">نعم</span>
                                    </div>
                                    <div class="px-8 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.foreignLicense === 'no' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.foreignLicense = 'no'">
                                        <span class="text-xs">لا</span>
                                    </div>
                                </div>
                            </AccordionContent>
                        </AccordionItem>

                        <!-- 2. Health Conditions -->
                        <AccordionItem value="healthConditions" class="border-b">
                            <AccordionHeader class="flex mb-0">
                                <AccordionTrigger
                                    class="px-0 flex flex-1 items-center justify-between py-4 font-medium transition-all [&[data-state=open]>svg.chevron]:rotate-180">
                                    <div class="flex justify-between w-full">
                                        <span class="text-sm font-bold mb-2">هل لدى مالك الوثيقة ظروف صحية أو قيود على
                                            الرخصة؟</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="shrink-0 text-white bg-green-500 rounded-full p-1 w-5 h-5 align-middle me-2">
                                            <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <svg viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg"
                                        class="chevron h-[10px] w-[10px] shrink-0 transition-transform duration-200">
                                        <path d="M1.25 1.375L6.5 6.625L11.75 1.375" stroke="currentColor"
                                            stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </AccordionTrigger>
                            </AccordionHeader>
                            <AccordionContent
                                class="overflow-hidden text-sm transition-all data-[state=closed]:animate-collapse-up data-[state=open]:animate-collapse-down">
                                <div class="pb-4 flex gap-2">
                                    <div class="px-8 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.healthConditions === 'yes' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.healthConditions = 'yes'">
                                        <span class="text-xs">نعم</span>
                                    </div>
                                    <div class="px-8 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.healthConditions === 'no' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.healthConditions = 'no'">
                                        <span class="text-xs">لا</span>
                                    </div>
                                </div>
                            </AccordionContent>
                        </AccordionItem>

                        <!-- 3. Traffic Violations -->
                        <AccordionItem value="trafficViolations" class="border-b">
                            <AccordionHeader class="flex mb-0">
                                <AccordionTrigger
                                    class="px-0 flex flex-1 items-center justify-between py-4 font-medium transition-all [&[data-state=open]>svg.chevron]:rotate-180">
                                    <div class="flex justify-between w-full">
                                        <span class="text-sm font-bold mb-2">هل لدى حامل الوثيقة مخالفات مرورية؟</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="shrink-0 text-white bg-green-500 rounded-full p-1 w-5 h-5 align-middle me-2">
                                            <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <svg viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg"
                                        class="chevron h-[10px] w-[10px] shrink-0 transition-transform duration-200">
                                        <path d="M1.25 1.375L6.5 6.625L11.75 1.375" stroke="currentColor"
                                            stroke-width="1.3125" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </AccordionTrigger>
                            </AccordionHeader>
                            <AccordionContent
                                class="overflow-hidden text-sm transition-all data-[state=closed]:animate-collapse-up data-[state=open]:animate-collapse-down">
                                <div class="pb-4 flex gap-2">
                                    <div class="px-8 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.trafficViolations === 'yes' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.trafficViolations = 'yes'">
                                        <span class="text-xs">نعم</span>
                                    </div>
                                    <div class="px-8 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-blue-600 hover:bg-blue-100 transition-colors"
                                        :class="otherDetails.trafficViolations === 'no' ? 'border-blue-600 bg-blue-100' : ''"
                                        @click="otherDetails.trafficViolations = 'no'">
                                        <span class="text-xs">لا</span>
                                    </div>
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </AccordionRoot>

                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<script setup>
import {
    DialogRoot, DialogPortal, DialogOverlay, DialogContent,
    DialogTitle, DialogDescription, DialogClose,
    AccordionRoot, AccordionItem, AccordionHeader, AccordionTrigger, AccordionContent,
} from 'radix-vue';
import AppSelect from '@/components/ui/AppSelect.vue';

defineProps( {
    otherDetails: { type: Object, required: true },
} );

const open = defineModel( 'open', { type: Boolean, default: false } );

// ── Options arrays (local to this sheet) ──────────────
const nightParkingOptions = [
    { value: '1', label: 'الشارع' },
    { value: '2', label: 'الممر المؤدي للمنزل' },
    { value: '3', label: 'المرآب' },
];

const expectedKMOptions = [
    { value: '1', label: '1-5,000' },
    { value: '2', label: '5,001-10,000' },
    { value: '3', label: '10,001-20,000' },
    { value: '4', label: '20,001-30,000' },
    { value: '5', label: 'أكثر من 30,000' },
];

const transmissionOptions = [
    { value: '1', label: 'أوتوماتيكي' },
    { value: '2', label: 'يدوي' },
];

const accidentOptions = [
    { value: '0', label: '0' },
    { value: '1', label: '1' },
    { value: '2', label: '2' },
    { value: '3', label: '3' },
    { value: '4', label: '4' },
    { value: '5', label: '5+' },
];

const educationOptions = [
    { value: '1', label: 'ابتدائي' },
    { value: '2', label: 'متوسط' },
    { value: '3', label: 'ثانوي' },
    { value: '4', label: 'دبلومة' },
    { value: '5', label: 'بكالوريوس' },
    { value: '6', label: 'ماجستير' },
    { value: '7', label: 'دكتوراه' },
];

const childrenOptions = [
    { value: '0', label: '0' },
    { value: '1', label: '1' },
    { value: '2', label: '2' },
    { value: '3', label: '3' },
    { value: '4', label: '4' },
    { value: '5', label: '5' },
    { value: '6', label: '6+' },
];
</script>
