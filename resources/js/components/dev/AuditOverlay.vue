<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="fixed bottom-4 right-4 z-[9999] w-80 rounded-lg bg-gray-900/95 text-white text-xs shadow-xl border border-gray-700 backdrop-blur"
            style="font-family: ui-monospace, monospace"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-700">
                <span class="font-semibold text-sm">🔍 Audit</span>
                <div class="flex gap-2">
                    <button
                        class="opacity-60 hover:opacity-100"
                        title="Download audit JSON"
                        @click="dump"
                    >
                        ⬇
                    </button>
                    <button
                        class="opacity-60 hover:opacity-100"
                        title="Reset counters"
                        @click="reset"
                    >
                        🔄
                    </button>
                    <button
                        class="opacity-60 hover:opacity-100"
                        title="Close (Ctrl+Shift+A)"
                        @click="visible = false"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-2 gap-x-3 gap-y-1 px-3 py-2">
                <div>
                    <span :class="wsColor">●</span> WS
                    <span class="text-gray-400 ml-1">{{ stats.channels }} ch</span>
                </div>
                <div>
                    📡 Polls
                    <span class="text-gray-400 ml-1">{{ stats.pollingTicks }}</span>
                </div>
                <div>
                    ⏱ Timers
                    <span class="text-gray-400 ml-1">{{ stats.activeTimers }}</span>
                </div>
                <div>
                    🗄 Mutations
                    <span class="text-gray-400 ml-1">{{ stats.storeMutations }}</span>
                </div>
                <div class="col-span-2">
                    <span :class="stats.errorCount > 0 ? 'text-red-400' : 'text-green-400'">
                        {{ stats.errorCount > 0 ? '❌' : '✅' }}
                    </span>
                    Errors
                    <span class="text-gray-400 ml-1">{{ stats.errorCount }}</span>
                </div>
            </div>

            <!-- Channels list (collapsible) -->
            <details v-if="stats.channelList.length" class="px-3 pb-2">
                <summary class="cursor-pointer text-gray-400 hover:text-white">
                    Channels ({{ stats.channelList.length }})
                </summary>
                <ul class="mt-1 max-h-24 overflow-y-auto space-y-0.5">
                    <li
                        v-for="ch in stats.channelList"
                        :key="ch"
                        class="text-gray-300 truncate"
                    >
                        {{ ch }}
                    </li>
                </ul>
            </details>

            <!-- Recent entries -->
            <details class="px-3 pb-2">
                <summary class="cursor-pointer text-gray-400 hover:text-white">
                    Recent ({{ entries.length }})
                </summary>
                <ul class="mt-1 max-h-32 overflow-y-auto space-y-0.5">
                    <li
                        v-for="(e, i) in recentEntries"
                        :key="i"
                        :class="{
                            'text-red-400': e.level === 'error',
                            'text-yellow-400': e.level === 'warn',
                            'text-gray-300': e.level === 'info',
                        }"
                        class="truncate"
                    >
                        <span class="text-gray-500">{{ formatTime(e.ts) }}</span>
                        [{{ e.cat }}] {{ e.msg }}
                    </li>
                </ul>
            </details>
        </div>

        <!-- Minimized badge (when overlay is hidden) -->
        <button
            v-if="!visible"
            class="fixed bottom-4 right-4 z-[9999] w-8 h-8 rounded-full bg-gray-900/80 text-white text-xs flex items-center justify-center shadow-lg border border-gray-700 hover:bg-gray-800 backdrop-blur"
            title="Open Audit (Ctrl+Shift+A)"
            @click="visible = true"
        >
            🔍
        </button>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { getEntries, resetAudit } from '@/utils/auditLogger';
import { getAuditStats } from '@/composables/useFrontendAudit';

const visible = ref( false );

// Refresh stats periodically
const stats = ref( getAuditStats() );
const entries = ref( getEntries() );
let refreshTimer = null;

function refreshStats ()
{
    stats.value = getAuditStats();
    entries.value = getEntries();
}

const recentEntries = computed( () =>
    [ ...entries.value ].reverse().slice( 0, 20 )
);

const wsColor = computed( () =>
    stats.value.channels > 0 ? 'text-green-400' : 'text-gray-500'
);

function formatTime ( ts )
{
    const d = new Date( ts );
    return `${ d.getHours().toString().padStart( 2, '0' ) }:${ d.getMinutes().toString().padStart( 2, '0' ) }:${ d.getSeconds().toString().padStart( 2, '0' ) }`;
}

function dump ()
{
    if ( window.__auditDump ) window.__auditDump();
}

function reset ()
{
    resetAudit();
    refreshStats();
}

function onKeydown ( e )
{
    if ( e.ctrlKey && e.shiftKey && e.key === 'A' )
    {
        e.preventDefault();
        visible.value = !visible.value;
    }
}

onMounted( () =>
{
    document.addEventListener( 'keydown', onKeydown );
    refreshTimer = setInterval( refreshStats, 2000 );
} );

onUnmounted( () =>
{
    document.removeEventListener( 'keydown', onKeydown );
    if ( refreshTimer ) clearInterval( refreshTimer );
} );
</script>
