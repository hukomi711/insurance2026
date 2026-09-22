<template>
    <div class="admin-sidebar-logo flex items-center gap-3 px-6 h-16"
        :style="{ borderBottomWidth: '1px', borderColor: 'var(--admin-sidebar-border)' }">
        <router-link to="/dashboard" class="flex items-center gap-3" aria-label="تأمينكم - لوحة التحكم">
            <div class="admin-sidebar-mark w-9 h-9 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-shield-halved w-5 h-5 text-white" aria-hidden="true"></i>
            </div>
            <div>
                <span class="admin-sidebar-title text-lg font-bold font-heading">تأمينكم</span>
                <span class="admin-sidebar-subtitle text-xs block -mt-1">لوحة التحكم</span>
            </div>
        </router-link>
    </div>
</template>

<style scoped>
.admin-sidebar-mark {
    background: linear-gradient(135deg, var(--admin-accent-blue) 0%, #1d4ed8 100%);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.22);
}

.admin-sidebar-title {
    color: var(--admin-sidebar-heading);
}

.admin-sidebar-subtitle {
    color: var(--admin-sidebar-text-muted);
}
</style>
