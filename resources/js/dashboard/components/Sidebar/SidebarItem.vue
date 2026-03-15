<template>
    <router-link :to="item.path"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors"
        :class="isActive ? 'bg-[var(--color-primary)] text-white' : ''"
        :style="isActive ? {} : { color: 'var(--admin-sidebar-text)' }"
        @mouseenter="!isActive && ($event.currentTarget.style.backgroundColor = 'var(--admin-sidebar-hover)')"
        @mouseleave="!isActive && ($event.currentTarget.style.backgroundColor = 'transparent')">
        <component :is="item.meta.icon" class="text-xl w-5 text-center" />
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
import { useBadgeStore } from '@/store';

const props = defineProps( {
    /** @type {{ path: string, meta: { title: string, icon: object, badgeKey?: string, activeMenu?: string } }} */
    item: {
        type: Object,
        required: true,
    },
} );

const route = useRoute();
const badgeStore = useBadgeStore();

const isActive = computed( () => {
    const target = props.item.meta.activeMenu || props.item.path;
    if ( target === '/dashboard' ) return route.path === '/dashboard';
    return route.path.startsWith( target );
} );

const badgeCount = computed( () => {
    if ( !props.item.meta.badgeKey ) return 0;
    return badgeStore.getBadge( props.item.meta.badgeKey );
} );

// Auto-mark section as seen when admin navigates to that page
watch( isActive, ( active ) => {
    if ( active && props.item.meta.badgeKey )
    {
        badgeStore.markSeen( props.item.meta.badgeKey );
    }
} );
</script>
