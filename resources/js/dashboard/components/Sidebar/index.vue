<template>
    <aside
        class="fixed inset-y-0 right-0 z-50 w-[85vw] max-w-xs sm:max-w-sm lg:w-64 transform transition-transform duration-300"
        :style="{ backgroundColor: 'var(--admin-sidebar-bg)', color: 'var(--admin-sidebar-text)' }"
        :class="[
            sidebarOpened ? 'translate-x-0' : 'translate-x-full',
            withoutAnimation ? 'duration-0!' : ''
        ]">
        <!-- Logo -->
        <SidebarLogo />

        <!-- Nav Links — generated from dashboard routes -->
        <nav aria-label="القائمة الرئيسية" class="mt-6 px-3 space-y-1">
            <SidebarItem v-for="menuItem in menuItems" :key="menuItem.path" :item="menuItem" />
        </nav>

        <!-- Bottom section -->
        <div class="absolute bottom-0 left-0 right-0 p-4"
            :style="{ borderTopWidth: '1px', borderColor: 'var(--admin-sidebar-border)' }">
            <router-link to="/"
                class="admin-sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm transition-colors">
                <IconLogout class="w-5 h-5" />
                العودة للموقع
            </router-link>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '@/store/modules/app';
import { IconLogout } from '@/icons';
import SidebarLogo from './SidebarLogo.vue';
import SidebarItem from './SidebarItem.vue';

const appStore = useAppStore();
const router = useRouter();

const sidebarOpened = computed( () => appStore.sidebarOpened );
const withoutAnimation = computed( () => appStore.withoutAnimation );

/**
 * Build menu items from dashboard route children
 * Each child must have meta.title and meta.icon to appear in sidebar
 */
const menuItems = computed( () => {
    const dashboardRoute = router.options.routes.find( ( r ) => r.path === '/dashboard' );
    if ( !dashboardRoute || !dashboardRoute.children ) return [];
    return dashboardRoute.children
        .filter( ( child ) => child.meta && child.meta.title && !child.meta.hidden )
        .map( ( child ) => ( {
            path: child.path === '' ? '/dashboard' : `/dashboard/${ child.path }`,
            meta: child.meta,
        } ) );
} );

// ⚠️ Badge auto-refresh removed — now handled centrally by adminPolling.js
</script>

<style scoped>
.admin-sidebar-link {
    color: var(--admin-sidebar-text);
}

.admin-sidebar-link:hover {
    color: var(--admin-sidebar-heading);
    background: var(--admin-sidebar-hover);
}
</style>
