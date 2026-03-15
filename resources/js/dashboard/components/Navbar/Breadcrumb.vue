<template>
    <nav aria-label="التنقل التفصيلي" class="flex items-center text-sm text-gray-500">
        <template v-for="(item, index) in breadcrumbs" :key="item.path">
            <span v-if="index > 0" class="mx-2 text-gray-300" aria-hidden="true">/</span>
            <router-link v-if="index < breadcrumbs.length - 1" :to="item.path"
                class="hover:text-[var(--color-primary)] transition-colors">
                {{ item.title }}
            </router-link>
            <span v-else class="text-gray-700 font-medium">{{ item.title }}</span>
        </template>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const breadcrumbs = computed( () => {
    const matched = route.matched.filter( ( r ) => r.meta && r.meta.title );
    return matched.map( ( r ) => ( {
        path: r.path || '/dashboard',
        title: r.meta.title,
    } ) );
} );
</script>
