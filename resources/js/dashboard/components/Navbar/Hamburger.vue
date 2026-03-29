<template>
    <button class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors" :aria-label="appStore.sidebarOpened ? 'إغلاق القائمة' : 'فتح القائمة'"
        @click="appStore.toggleSidebar()">
        <i class="w-6 h-6 transition-transform duration-200" :class="appStore.sidebarOpened ? 'fa-solid fa-xmark rotate-0' : 'fa-solid fa-bars rotate-180'" aria-hidden="true"></i>
    </button>
</template>

<script setup>
import { useAppStore } from '@/store/modules/app';

const appStore = useAppStore();
</script>
