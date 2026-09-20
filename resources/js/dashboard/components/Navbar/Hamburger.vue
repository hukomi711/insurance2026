<template>
    <button
        class="admin-navbar-action admin-touch inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
        :aria-label="appStore.sidebarOpened ? 'إغلاق القائمة' : 'فتح القائمة'"
        @click="appStore.toggleSidebar()">
        <i class="w-6 h-6 text-lg leading-none transition-transform duration-200"
           :class="appStore.sidebarOpened ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"
           aria-hidden="true"></i>
    </button>
</template>

<script setup>
import { useAppStore } from '@/store/modules/app';

const appStore = useAppStore();
</script>

<style scoped>
.admin-navbar-action { color: var(--admin-text-muted); }
.admin-navbar-action:hover { color: var(--admin-text); background: var(--admin-hover); }
</style>
