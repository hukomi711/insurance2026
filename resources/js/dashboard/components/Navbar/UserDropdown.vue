<template>
    <div class="flex items-center gap-2 pr-3 border-r border-gray-200">
        <div
            class="w-8 h-8 bg-[var(--color-primary-dark)] rounded-full flex items-center justify-center text-white text-sm font-bold">
            {{ userStore.initials }}
        </div>
        <div class="hidden sm:block">
            <p class="text-sm font-medium text-gray-700 leading-none">{{ userStore.name }}</p>
            <p class="text-xs text-gray-400">{{ userStore.email }}</p>
        </div>
    </div>
</template>

<script setup>
import { useUserStore } from '@/store';

const userStore = useUserStore();
</script>
