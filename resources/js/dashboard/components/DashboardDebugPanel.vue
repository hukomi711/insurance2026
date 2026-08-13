<script setup>
/**
 * DashboardDebugPanel — shows real-time status of polling, WebSocket, and updates
 *
 * Visible only when:
 * 1. Current user is an ADMIN
 * 2. AND (?debug=1 in URL OR localStorage.getItem('dashboard_debug') === 'true')
 *
 * Non-admin users cannot access debug mode even with ?debug=1 parameter.
 *
 * Shows:
 * - Polling status (active/paused)
 * - WebSocket connection state
 * - Last refresh timestamp
 * - Last event received
 * - Error count
 */

import { computed } from 'vue';
import logger from '@/utils/logger';
import { useUserStore } from '@/store/modules/user';

const userStore = useUserStore();

const props = defineProps({
    autoRefreshEnabled: { type: Boolean, default: false },
    wsConnected: { type: Boolean, default: false },
    wsState: { type: String, default: 'disconnected' },
    lastRefreshedAt: { type: Number, default: 0 },
    refreshErrorCount: { type: Number, default: 0 },
});

const isDebugMode = computed(() => {
    if (typeof window === 'undefined') return false;

    // Only allow admin users to enable debug mode
    if (userStore.role !== 'admin') {
        return false;
    }

    return window.location.search.includes('debug=1') ||
           localStorage.getItem('dashboard_debug') === 'true';
});

const statusText = computed(() => {
    const parts = [];

    // Polling status
    parts.push(props.autoRefreshEnabled ? '✓ Polling' : '✗ Polling OFF');

    // WebSocket status
    if (props.wsState === 'connected' || props.wsState === 'ready') {
        parts.push('✓ WS');
    } else {
        parts.push(`✗ WS (${props.wsState})`);
    }

    // Last refresh
    if (props.lastRefreshedAt) {
        const seconds = Math.floor((Date.now() - props.lastRefreshedAt) / 1000);
        parts.push(`Last: ${seconds}s ago`);
    }

    // Errors
    if (props.refreshErrorCount > 0) {
        parts.push(`⚠ ${props.refreshErrorCount} errors`);
    }

    return parts.join(' • ');
});

const statusClass = computed(() => {
    if (!isDebugMode.value) return 'hidden';

    if (props.autoRefreshEnabled &&
        (props.wsState === 'connected' || props.wsState === 'ready')) {
        return 'bg-emerald-900 border-emerald-700';  // All good
    }
    if (!props.autoRefreshEnabled) {
        return 'bg-amber-900 border-amber-700';       // Polling disabled
    }
    return 'bg-red-900 border-red-700';               // Error state
});

function toggleDebugMode() {
    if (localStorage.getItem('dashboard_debug') === 'true') {
        localStorage.removeItem('dashboard_debug');
    } else {
        localStorage.setItem('dashboard_debug', 'true');
    }
    window.location.reload();
}

function enableVerboseLogs() {
    logger.setVerbose(true);
    console.log('[Dashboard Debug] Verbose logging enabled');
}

function getEchoStatus() {
    if (typeof window === 'undefined' || !window.Echo) {
        return 'Echo not loaded';
    }
    const state = window.Echo?.connector?.pusher?.connection?.state;
    return `Pusher: ${state || 'unknown'}`;
}
</script>

<template>
    <div
        v-if="isDebugMode"
        class="fixed bottom-4 right-4 z-999 bg-slate-800 border rounded-lg p-3 text-xs text-slate-200 font-mono max-w-sm space-y-2"
        :class="statusClass"
    >

        <div class="font-bold text-slate-100">🔧 Dashboard Debug</div>
        <div class="space-y-1 text-[11px]">
            <div>Status: {{ statusText }}</div>
            <div>{{ getEchoStatus() }}</div>
            <div>Auth: {{ localStorage.getItem('auth_token') ? '✓ Token' : '✗ No Token' }}</div>
        </div>

        <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-600">
            <button
                class="px-2 py-1 bg-slate-700 hover:bg-slate-600 rounded text-[10px]"
                @click="enableVerboseLogs"
            >
                Verbose
            </button>
            <button
                class="px-2 py-1 bg-slate-700 hover:bg-slate-600 rounded text-[10px]"
                @click="toggleDebugMode"
            >
                Close
            </button>
        </div>
    </div>
</template>

<style scoped>
.hidden {
    display: none !important;
}
</style>
