<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 bg-black/50 z-50 data-[state=open]:animate-fade-in" />
            <DialogContent class="fixed inset-y-0 left-0 z-50 w-[90vw] sm:w-[38rem] bg-white shadow-lg flex flex-col
                       data-[state=open]:animate-slide-in-left data-[state=closed]:animate-slide-out-left">
                <!-- Close button -->
                <div class="text-center sm:text-left flex justify-end items-end">
                    <DialogClose
                        class="flex align-middle rounded-full p-2 bg-slate-100 mt-2 mx-2 cursor-pointer hover:bg-slate-200 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none"
                            class="shrink-0 w-5 h-5 text-slate-600 font-bold">
                            <path d="M5 5L15 15" stroke="currentColor" stroke-width="1.13333" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M15 5L5 15" stroke="currentColor" stroke-width="1.13333" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </DialogClose>
                </div>

                <!-- Scrollable content -->
                <div dir="rtl" class="overflow-y-auto h-full px-4">
                    <div class="flex flex-col m-6 flex-1">
                        <!-- Title row -->
                        <div class="flex justify-between items-center gap-4 mb-4">
                            <div>
                                <DialogTitle class="text-lg font-bold text-slate-900">قائمة السائقين</DialogTitle>
                                <p class="text-slate-500 text-sm">
                                    لضمان تغطية التأمين لجميع سائقي المركبة يجب إدخال كافات البيانات المطلوبة
                                </p>
                            </div>
                            <button type="button" class="cursor-pointer whitespace-nowrap text-sm font-medium transition-colors
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring
                                       size-9 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200/80
                                       active:bg-blue-300 inline-flex items-center justify-center shrink-0" @click="emit( 'add-driver' )">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-5 h-5">
                                    <path d="M12 5V19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>

                        <!-- Empty state -->
                        <div v-if="drivers.length === 0" class="flex justify-center items-center h-[100%]">
                            <div class="flex flex-col items-center justify-center">
                                <img :src="noDriversSrc" alt="no drivers" class="max-w-full mb-4" loading="lazy" width="200" height="200" />
                                <span class="font-bold text-xs">لا يوجد سائقين</span>
                                <span class="text-xs text-slate-500 mt-2">يجب إضافة سائق واحد على الأقل.</span>
                            </div>
                        </div>

                        <!-- Drivers list -->
                        <div v-else class="space-y-3 mb-4">
                            <div v-for="( driver, index ) in drivers" :key="driver.id"
                                class="border border-slate-200 rounded-lg p-4 relative group">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center shrink-0 mt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="w-5 h-5 text-blue-500">
                                            <path
                                                d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M20 21C20 17.13 16.42 14 12 14C7.58 14 4 17.13 4 21"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <div class="relative">
                                            <input :id="`driver-name-${index}`" v-model="driver.name" type="text"
                                                :name="`driver-name-${index}`" autocomplete="off"
                                                :aria-label="driver.isPolicyHolder ? 'اسم مالك الوثيقة' : `اسم السائق ${index + 1}`"
                                                class="w-full text-sm font-medium text-slate-800 bg-slate-50 border border-slate-200
                                                       rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400
                                                       focus:border-blue-400 transition-all placeholder:text-slate-400"
                                                :placeholder="driver.isPolicyHolder ? 'مالك الوثيقة' : 'اسم السائق ' + ( index + 1 )" />
                                        </div>
                                        <div class="relative">
                                            <input :id="`driver-nationalId-${index}`" v-model="driver.nationalId" type="text"
                                                maxlength="10" :name="`driver-nationalId-${index}`"
                                                autocomplete="off"
                                                :aria-label="`رقم هوية السائق ${index + 1}`" class="w-full text-sm text-slate-600 bg-slate-50 border border-slate-200
                                                       rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400
                                                       focus:border-blue-400 transition-all placeholder:text-slate-400"
                                                placeholder="رقم الهوية"
                                                @blur="onDriverNationalIdBlur( index, driver.nationalId )" />
                                            <p v-if="driverErrors[ index ]" class="text-red-500 text-xs mt-1">{{ driverErrors[ index ] }}</p>
                                        </div>
                                    </div>
                                    <button v-if="!driver.isPolicyHolder" type="button"
                                        class="text-red-400 hover:text-red-600 transition-colors cursor-pointer p-1.5 mt-1
                                               hover:bg-red-50 rounded-md"
                                        @click="emit( 'remove-driver', index )">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="w-4 h-4">
                                            <path d="M3 6H5H21" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                            <path
                                                d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <span v-else
                                        class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded mt-2 shrink-0">مالك</span>
                                </div>
                            </div>
                        </div>

                        <!-- Checkbox: add policy holder as driver -->
                        <div class="flex w-full flex-wrap items-center gap-2 mb-4 mt-3">
                            <div class="flex items-center gap-2 group min-w-fit">
                                <CheckboxRoot id="addPolicyHolderAsdriverBTN" name="addPolicyHolderAsDriver"
                                    :checked="addPolicyHolderAsDriver"
                                    aria-label="checkbox" class="peer rounded-xs shrink-0 transition-all duration-100
                                           border border-solid border-slate-300 size-6 cursor-pointer
                                           data-[state=checked]:bg-primary data-[state=checked]:border-primary
                                           data-[state=checked]:text-white
                                           hover:border-primary"
                                    @update:checked="( val ) => emit( 'toggle-policy-holder', val )">
                                    <CheckboxIndicator class="flex items-center justify-center text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="w-4 h-4">
                                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </CheckboxIndicator>
                                </CheckboxRoot>
                            </div>
                            <label class="cursor-pointer text-sm" for="addPolicyHolderAsdriverBTN">إضافة مالك الوثيقة
                                كسائق</label>
                        </div>
                    </div>

                    <DialogDescription class="sr-only">إدارة قائمة سائقي المركبة</DialogDescription>
                </div>

                <!-- Bottom bar -->
                <div class="border-t border-slate-200 p-4 flex justify-end gap-2" dir="rtl">
                    <button type="button" class="cursor-pointer whitespace-nowrap transition-colors
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring
                               min-h-9 min-w-[7.5rem] px-4 py-2 text-xs font-medium rounded-sm gap-2
                               bg-blue-200 text-blue-600 hover:bg-blue-300/80 active:bg-blue-300
                               inline-flex items-center justify-center" @click="emit( 'add-driver' )">
                        <div class="flex items-center w-full gap-2 justify-center">
                            <span class="flex-shrink-0" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M12 5V19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>إضافة سائق إضافي</span>
                        </div>
                    </button>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<script setup>
import { reactive } from 'vue';
import {
    CheckboxRoot, CheckboxIndicator,
    DialogRoot, DialogPortal, DialogOverlay, DialogContent,
    DialogTitle, DialogDescription, DialogClose,
} from 'radix-vue';
import { validateNationalId } from '@/utils/nationalIdValidation';

defineProps( {
    drivers: { type: Array, required: true },
    addPolicyHolderAsDriver: { type: Boolean, default: false },
    noDriversSrc: { type: String, required: true },
} );

const open = defineModel( 'open', { type: Boolean, default: false } );

const emit = defineEmits( [ 'add-driver', 'remove-driver', 'toggle-policy-holder' ] );

// Per-driver validation errors keyed by driver index
const driverErrors = reactive( {} );

function onDriverNationalIdBlur ( index, value )
{
    if ( !value ) {
        driverErrors[ index ] = '';
        return;
    }
    const { error } = validateNationalId( value, { context: 'blur' } );
    driverErrors[ index ] = error;
}
</script>
