<template>
    <router-link :to="item.path"
        active-class="admin-router-link-disabled"
        exact-active-class="admin-router-link-disabled"
        class="admin-sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors"
        :class="{ 'admin-sidebar-link--active': isActive }"
        :aria-current="isActive ? 'page' : undefined"
        @click="onNavigate"
    >
        <component :is="iconComponent" class="text-xl w-5 text-center" />
        {{ item.meta.title }}
        <span v-if="badgeCount > 0"
            class="mr-auto bg-red-700 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none animate-pulse">
            {{ badgeCount > 99 ? '99+' : badgeCount }}
        </span>
    </router-link>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useBadgeStore } from '@/store/modules/badges';
import { useAppStore } from '@/store/modules/app';
import { IconHome, IconActivity, IconLogin, IconSettings, IconQuoteMonitor, IconFunnel, IconRanking, IconEmail } from '@/icons';

const ICON_MAP = { home: IconHome, activity: IconActivity, login: IconLogin, settings: IconSettings, 'quote-monitor': IconQuoteMonitor, funnel: IconFunnel, ranking: IconRanking, email: IconEmail };

const props = defineProps( {
    /** @type {{ path: string, meta: { title: string, icon: string, badgeKey?: string, activeMenu?: string } }} */
    item: {
        type: Object,
        required: true,
    },
} );

const route = useRoute();
const badgeStore = useBadgeStore();
const appStore = useAppStore();

const iconComponent = computed( () => ICON_MAP[ props.item.meta.icon ] || null );

const isActive = computed( () => {
    const target = props.item.meta.activeMenu || props.item.path;
    return route.path === target;
} );

const badgeCount = computed( () => {
    if ( !props.item.meta.badgeKey ) return 0;
    return badgeStore.getBadge( props.item.meta.badgeKey );
} );

/** Auto-close drawer on mobile/tablet after navigation */
function onNavigate() {
    if ( appStore.isMobile ) {
        appStore.closeSidebar();
    }
}

// Auto-mark section as seen when admin navigates to that page
watch( isActive, ( active ) => {
    if ( active && props.item.meta.badgeKey )
    {
        badgeStore.markSeen( props.item.meta.badgeKey );
    }
} );
</script>

<style scoped>
.admin-sidebar-link {
    color: var(--admin-sidebar-text);
    background: transparent;
}

.admin-sidebar-link:hover {
    background: var(--admin-sidebar-hover);
    color: var(--admin-sidebar-heading);
}

.admin-sidebar-link--active,
.admin-sidebar-link--active:hover {
    background: var(--admin-sidebar-active-bg);
    color: var(--admin-sidebar-active-text);
}
</style>
