<template>
    <div class="flex flex-col gap-2">
        <PopoverRoot v-model:open="showCalendar">
            <PopoverTrigger as-child>
                <button type="button"
                    class="cursor-pointer whitespace-nowrap rounded-lg border border-slate-300 hover:border-slate-400 active:border-slate-500 font-normal w-full bg-white transition-all typ-b2 text-start relative p-4 inline-flex items-center justify-center"
                    :class="{ 'border-blue-600': showCalendar }">
                    <div class="flex items-center w-full gap-2 justify-between">
                        <div class="w-full overflow-hidden">
                            <span v-if="!modelValue" class="first-letter:capitalize text-slate-500">اختر التاريخ...</span>
                            <span v-else class="text-slate-900">{{ formattedDate }}</span>
                        </div>
                        <span class="shrink-0" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                class="shrink-0 w-5 h-5">
                                <path d="M16 2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M8 2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M3 9H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M19 4H5C3.895 4 3 4.895 3 6V19C3 20.105 3.895 21 5 21H19C20.105 21 21 20.105 21 19V6C21 4.895 20.105 4 19 4Z"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </button>
            </PopoverTrigger>
            <PopoverPortal>
                <PopoverContent :side-offset="8" side="bottom" align="start"
                    class="z-100 bg-white border border-slate-200 rounded-xl shadow-md p-4 w-auto data-[state=open]:animate-scale-in">
                    <CalendarRoot v-slot="{ grid, weekDays }" v-model="calendarValue" locale="ar-SA-u-nu-latn"
                        class="w-[320px]" :min-value="minDate" :max-value="maxDate"
                        @update:model-value="onDateSelected">
                        <CalendarHeader class="flex items-center justify-between mb-4">
                            <CalendarPrev
                                class="w-8 h-8 rounded-full border border-slate-200 hover:bg-slate-100 flex items-center justify-center cursor-pointer transition-colors">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </CalendarPrev>
                            <CalendarHeading class="text-sm font-semibold" />
                            <CalendarNext
                                class="w-8 h-8 rounded-full border border-slate-200 hover:bg-slate-100 flex items-center justify-center cursor-pointer transition-colors">
                                <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4">
                                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </CalendarNext>
                        </CalendarHeader>
                        <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full">
                            <CalendarGridHead>
                                <CalendarGridRow class="flex justify-between">
                                    <CalendarHeadCell v-for="day in weekDays" :key="day"
                                        class="w-12 h-10 flex items-center justify-center text-xs text-slate-500 font-medium">
                                        {{ day }}
                                    </CalendarHeadCell>
                                </CalendarGridRow>
                            </CalendarGridHead>
                            <CalendarGridBody>
                                <CalendarGridRow v-for="(weekDates, index) in month.rows"
                                    :key="`week-${index}`" class="flex justify-between mt-1">
                                    <CalendarCell v-for="weekDate in weekDates" :key="weekDate.toString()"
                                        :date="weekDate" class="w-12 h-12 flex items-center justify-center">
                                        <CalendarCellTrigger :day="weekDate" :month="month.value"
                                            class="w-8 h-8 rounded-full text-sm flex items-center justify-center cursor-pointer transition-colors
                                                   hover:bg-blue-100 hover:text-blue-600
                                                   data-selected:bg-blue-600 data-selected:text-white
                                                   data-disabled:text-slate-300 data-disabled:cursor-not-allowed
                                                   data-outside-month:text-slate-300
                                                   data-today:font-bold data-today:text-blue-600
                                                   data-selected:data-today:text-white" />
                                    </CalendarCell>
                                </CalendarGridRow>
                            </CalendarGridBody>
                        </CalendarGrid>
                    </CalendarRoot>
                </PopoverContent>
            </PopoverPortal>
        </PopoverRoot>
        <p v-if="error" class="text-xs text-red-500 mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    PopoverRoot, PopoverTrigger, PopoverContent, PopoverPortal,
    CalendarRoot, CalendarHeader, CalendarHeading, CalendarGrid,
    CalendarCell, CalendarHeadCell, CalendarNext, CalendarPrev,
    CalendarGridHead, CalendarGridBody, CalendarGridRow, CalendarCellTrigger,
} from 'radix-vue';
import { today, getLocalTimeZone } from '@internationalized/date';

const props = defineProps( {
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
} );

const emit = defineEmits( [ 'update:modelValue' ] );

const showCalendar = ref( false );
const calendarValue = ref();

const minDate = today( getLocalTimeZone() );
const maxDate = minDate.add( { days: 30 } );

const formattedDate = computed( () => {
    if ( !props.modelValue ) return '';
    const d = new Date( props.modelValue );
    return d.toLocaleDateString( 'ar-SA-u-nu-latn', { year: 'numeric', month: 'long', day: 'numeric' } );
} );

function onDateSelected ( dateValue ) {
    if ( dateValue ) {
        const d = dateValue.toDate
            ? dateValue.toDate( 'UTC' )
            : new Date( dateValue.year, dateValue.month - 1, dateValue.day );
        emit( 'update:modelValue', d.toISOString().split( 'T' )[ 0 ] );
        showCalendar.value = false;
    }
}
</script>
