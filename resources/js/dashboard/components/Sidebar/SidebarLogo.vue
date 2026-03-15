<template>
    <div class="flex items-center gap-3 px-6 h-16"
        :style="{ borderBottomWidth: '1px', borderColor: 'var(--admin-sidebar-border)' }">
        <router-link to="/dashboard" class="flex items-center gap-3" aria-label="تأمينكم - لوحة التحكم">
            <div class="w-9 h-9 bg-[var(--color-primary)] rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-shield-halved w-5 h-5 text-white" aria-hidden="true"></i>
            </div>
            <div>
                <span class="text-lg font-bold font-heading text-white">تأمينكم</span>
                <span class="text-xs block -mt-1" :style="{ color: 'var(--admin-text-dim)' }">لوحة التحكم</span>
            </div>
        </router-link>
    </div>
</template>
