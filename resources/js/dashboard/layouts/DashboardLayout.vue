<template>
    <div class="min-h-screen flex overflow-x-hidden transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-bg)' }"
        dir="rtl" data-admin-theme="light">
        <!-- Sidebar -->
        <Sidebar />

        <!-- Overlay for mobile -->
        <div v-if="appStore.sidebarOpened && appStore.isMobile" class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            aria-hidden="true" @click="appStore.closeSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0 transition-[margin] duration-300" :class="appStore.sidebarOpened ? 'lg:mr-64' : ''">
            <Navbar />
            <AppMain />
        </div>

        <!-- Dev-only audit overlay -->
        <AuditOverlay v-if="isDev" />
        
        <!-- Debug panel (visible when ?debug=1 or localStorage.dashboard_debug=true) -->
        <DashboardDebugPanel />
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, provide, defineAsyncComponent, watch } from 'vue';
import { useAppStore } from '@/store/modules/app';
import { useBadgeStore } from '@/store/modules/badges';
import { useNotificationsStore } from '@/store/modules/notifications';
import { startAdminPolling, stopAdminPolling, setTabVisible } from '@/services/adminPolling';
import { OPEN_CHAT_TARGET } from '../dashboardKeys';
import { useTheme } from '../composables/useTheme';
import { useBreakpoint } from '../composables/useBreakpoint';
import { normalizeInputDigits, toLatinDigits } from '../utils/latinDigits';
import logger from '@/utils/logger';
import Sidebar from '../components/Sidebar/index.vue';
import Navbar from '../components/Navbar/index.vue';
import AppMain from '../components/AppMain.vue';
import DashboardDebugPanel from '../components/DashboardDebugPanel.vue';

const isDev = import.meta.env.DEV;
const AuditOverlay = isDev
    ? defineAsyncComponent( () => import( '@/components/dev/AuditOverlay.vue' ) )
    : null;

const appStore = useAppStore();
const { init: initTheme } = useTheme();

// Bridge: allow child pages to open a chat in LiveChatDropdown
const openChatTarget = ref( null );
provide( OPEN_CHAT_TARGET, openChatTarget );

/** Detect compact device (< Tailwind lg = 1024px) and sync sidebar */
const { isCompact } = useBreakpoint();

watch(
    isCompact,
    ( compact ) => {
        appStore.toggleDevice( compact ? 'mobile' : 'desktop' );
        if ( compact ) {
            appStore.closeSidebar( true );
        } else if ( !appStore.sidebarOpened ) {
            appStore.openSidebar();
        }
    },
    { immediate: true }
);

function handleVisibility () {
    setTabVisible( document.visibilityState === 'visible' );
}

function handleKeydown( e ) {
    if ( e.key === 'Escape' && appStore.sidebarOpened && appStore.isMobile ) {
        appStore.closeSidebar();
    }
}

/**
 * Force Latin digits on every <input>/<textarea> within the dashboard,
 * regardless of UI language. Runs in the capture phase so it executes
 * before v-model handlers see the value.
 */
function handleDashboardInput ( e ) {
    const t = e.target;
    if ( t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement ) {
        normalizeInputDigits( e );
    }
}

/**
 * Convert any Arabic-Indic / Eastern-Arabic digits in already-rendered
 * text nodes inside the dashboard to Latin. Covers third-party widgets
 * and v-html outputs not formatted via our utilities.
 */
let digitObserver = null;
function normalizeRenderedDigits ( root ) {
    if ( !root ) return;
    const walker = document.createTreeWalker( root, NodeFilter.SHOW_TEXT, {
        acceptNode: ( node ) => /[\u0660-\u0669\u06f0-\u06f9]/.test( node.nodeValue )
            ? NodeFilter.FILTER_ACCEPT
            : NodeFilter.FILTER_REJECT,
    } );
    const queue = [];
    let n;
    while ( ( n = walker.nextNode() ) ) queue.push( n );
    for ( const node of queue ) {
        node.nodeValue = toLatinDigits( node.nodeValue );
    }
}

onMounted( () => {
    initTheme();
    document.addEventListener( 'visibilitychange', handleVisibility );
    document.addEventListener( 'keydown', handleKeydown );
    // Global input normalizer — capture phase, runs before v-model.
    document.addEventListener( 'input', handleDashboardInput, true );

    // One-time sweep for already-rendered nodes, then observe for changes.
    normalizeRenderedDigits( document.body );
    digitObserver = new MutationObserver( ( mutations ) => {
        for ( const m of mutations ) {
            if ( m.type === 'characterData' ) {
                if ( m.target?.nodeValue && /[\u0660-\u0669\u06f0-\u06f9]/.test( m.target.nodeValue ) ) {
                    m.target.nodeValue = toLatinDigits( m.target.nodeValue );
                }
            } else {
                m.addedNodes.forEach( ( node ) => {
                    if ( node.nodeType === Node.TEXT_NODE ) {
                        if ( /[\u0660-\u0669\u06f0-\u06f9]/.test( node.nodeValue ) ) {
                            node.nodeValue = toLatinDigits( node.nodeValue );
                        }
                    } else if ( node.nodeType === Node.ELEMENT_NODE ) {
                        normalizeRenderedDigits( node );
                    }
                } );
            }
        }
    } );
    digitObserver.observe( document.body, {
        childList: true,
        subtree: true,
        characterData: true,
    } );

    // Keep production admin console quiet by default.
    // Verbose dashboard logs can still be enabled manually with logger.setVerbose(true).
    if ( import.meta.env.PROD && logger.isVerbose() ) {
        logger.setVerbose( false );
    }

    // 🎯 مؤقت واحد مركزي — بدل 3+ timers منفصلة
    const badgeStore = useBadgeStore();
    const notificationsStore = useNotificationsStore();
    startAdminPolling( { badgeStore, notificationsStore } );

    // 🔍 Dev-only: initialize audit system
    if ( isDev ) {
        import( '@/composables/useFrontendAudit' ).then( ( { useFrontendAudit } ) => {
            useFrontendAudit();
        } );
    }
} );

onBeforeUnmount( () => {
    document.removeEventListener( 'visibilitychange', handleVisibility );
    document.removeEventListener( 'keydown', handleKeydown );
    document.removeEventListener( 'input', handleDashboardInput, true );
    if ( digitObserver ) {
        digitObserver.disconnect();
        digitObserver = null;
    }
    stopAdminPolling();
} );
</script>
