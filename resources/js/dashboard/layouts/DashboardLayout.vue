<template>
    <div class="min-h-screen flex transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-bg)' }"
        dir="rtl" data-admin-theme="light">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Overlay for mobile -->
        <div v-if="appStore.sidebarOpened && appStore.isMobile" class="fixed inset-0 bg-black/50 z-20 lg:hidden"
            aria-hidden="true" @click="appStore.closeSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 transition-[margin] duration-300" :class="appStore.sidebarOpened ? 'lg:mr-64' : ''">
            <Navbar />
            <AppMain />
        </div>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, provide } from 'vue';
import { useAppStore, useBadgeStore, useNotificationsStore } from '@/store';
import { startAdminPolling, stopAdminPolling } from '@/services/adminPolling';
import { OPEN_CHAT_TARGET } from '../dashboardKeys';
import { useTheme } from '../composables/useTheme';
import logger from '@/utils/logger';
import Sidebar from '../components/Sidebar/index.vue';
import Navbar from '../components/Navbar/index.vue';
import AppMain from '../components/AppMain.vue';

const appStore = useAppStore();
const { init: initTheme } = useTheme();

// Bridge: allow child pages to open a chat in LiveChatDropdown
const openChatTarget = ref( null );
provide( OPEN_CHAT_TARGET, openChatTarget );

/** Detect mobile device and auto-close sidebar */
const MOBILE_WIDTH = 1024;

function handleResize() {
    const isMobile = window.innerWidth < MOBILE_WIDTH;
    appStore.toggleDevice( isMobile ? 'mobile' : 'desktop' );
    if ( isMobile ) {
        appStore.closeSidebar( true );
    }
}

onMounted( () => {
    initTheme();
    handleResize();
    window.addEventListener( 'resize', handleResize, { passive: true } );

    // 🔊 لوحة التحكم: تفعيل السجلات دائماً
    if ( !logger.isVerbose() ) {
        logger.setVerbose( true );
    }

    // 🎯 مؤقت واحد مركزي — بدل 3+ timers منفصلة
    const badgeStore = useBadgeStore();
    const notificationsStore = useNotificationsStore();
    startAdminPolling( { badgeStore, notificationsStore } );
} );

onBeforeUnmount( () => {
    window.removeEventListener( 'resize', handleResize );
    stopAdminPolling();
} );
</script>
