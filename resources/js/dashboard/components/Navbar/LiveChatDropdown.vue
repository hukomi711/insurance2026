<template>
    <div ref="containerRef" class="relative">
        <!-- Chat Button — matches Navbar light theme -->
        <button class="relative p-2 rounded-lg transition-colors"
            :class="[
                unreadCount > 0
                    ? 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100'
                    : 'text-gray-500 hover:bg-gray-100',
            ]" title="الدردشة المباشرة" @click.stop="toggleDropdown">
            <div class="relative">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>

                <!-- Badge -->
                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-50" enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-50">
                    <span v-if="unreadCount > 0"
                        class="absolute -right-2 -top-2 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                </Transition>

                <!-- Pulse -->
                <span v-if="hasNewMessage"
                    class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
        </button>

        <!-- Dropdown Panel — light theme matching notifications -->
        <Transition enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1">
            <div v-if="isOpen"
                class="absolute left-0 top-full z-50 mt-2 w-[380px] max-w-[calc(100vw-1rem)] overflow-hidden rounded-xl bg-white shadow-xl border border-gray-200 flex flex-col"
                dir="rtl" @click.stop>

                <!-- Conversation View -->
                <template v-if="selectedConversation">
                    <!-- Header -->
                    <div
                        class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                        <button class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all"
                            @click.stop="backToList">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div
                                class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold text-sm">
                                {{ (getDisplayName(selectedConversation))[0] }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-gray-800 text-sm truncate">
                                    {{ getDisplayName(selectedConversation) }}
                                </h3>
                                <p class="text-[11px] text-gray-400 font-mono">
                                    {{ selectedConversation.visitor_ip }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            متصل
                        </span>
                    </div>

                    <!-- Messages Container -->
                    <div ref="messagesContainerRef"
                        class="h-80 overflow-y-auto p-4 space-y-3 bg-gray-50 scroll-smooth">

                        <!-- Loading -->
                        <div v-if="isLoadingConversation" class="flex items-center justify-center py-16">
                            <i class="fa-solid fa-spinner fa-spin text-gray-400 text-xl"></i>
                        </div>

                        <!-- Messages -->
                        <template v-else>
                            <TransitionGroup name="message">
                                <div v-for="msg in conversationMessages" :key="msg.id" class="flex"
                                    :class="msg.sender === 'user' ? 'justify-start' : 'justify-end'">

                                    <div class="max-w-[80%] group" :class="{ 'opacity-60': msg._pending }">

                                        <!-- Message Bubble -->
                                        <div class="rounded-2xl px-4 py-2.5 shadow-sm transition-all"
                                            :class="[
                                                msg.sender === 'user'
                                                    ? 'bg-white border border-gray-200 rounded-tl-md text-gray-800'
                                                    : msg.sender === 'admin'
                                                        ? 'bg-emerald-500 rounded-tr-md text-white'
                                                        : 'bg-gray-200 rounded-tr-md text-gray-700',
                                            ]">
                                            <p class="text-[13px] leading-relaxed whitespace-pre-wrap">
                                                {{ msg.message }}</p>
                                        </div>

                                        <!-- Time -->
                                        <div class="flex items-center gap-1 mt-1 px-1"
                                            :class="msg.sender === 'user' ? 'justify-start' : 'justify-end'">
                                            <span class="text-[10px] text-gray-400">
                                                {{ formatMessageTime(msg.created_at) }}
                                            </span>
                                            <svg v-if="msg.sender === 'admin' && !msg._pending"
                                                class="w-3 h-3 text-emerald-500" fill="currentColor"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                            </svg>
                                            <svg v-if="msg._pending"
                                                class="w-3 h-3 text-gray-400 animate-spin" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                                                </path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </TransitionGroup>
                        </template>


</div>

                    <!-- Reply Input -->
                    <div class="border-t border-gray-100 bg-white p-3">
                        <div class="flex items-end gap-2">
                            <div class="flex-1 relative">
                                <textarea id="livechat-reply" ref="inputRef" v-model="replyText"
                                    rows="1" name="livechat-reply" autocomplete="off" aria-label="رد على المحادثة"
                                    placeholder="اكتب رسالتك..."
                                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-gray-800 text-sm placeholder-gray-400 focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500/40 resize-none transition-all outline-none"
                                    style="max-height: 120px; min-height: 44px;"
                                    @keydown.enter.exact.prevent="sendReplyMessage"
                                    @input="e => { e.target.style.height = 'auto'; e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px'; }"></textarea>
                            </div>

                            <button :disabled="!replyText.trim() || isSending"
                                class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-500 text-white hover:bg-emerald-600 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-500 transition-all"
                                @click="sendReplyMessage">
                                <svg v-if="isSending" class="animate-spin h-5 w-5" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg v-else class="h-5 w-5 rotate-90 -mr-0.5" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Conversations List -->
                <template v-else>
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm">الدردشة المباشرة</h3>
                                <p class="text-[11px] text-gray-400">{{ conversations.length }} محادثة نشطة
                                </p>
                            </div>
                        </div>

                        <button class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all"
                            :class="{ 'animate-spin': isLoading }"
                            @click.stop="fetchConversations">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>

                    <!-- List -->
                    <div class="max-h-[380px] overflow-y-auto flex-1">
                        <!-- Loading -->
                        <div v-if="isLoading && conversations.length === 0"
                            class="flex flex-col items-center justify-center py-16">
                            <i class="fa-solid fa-spinner fa-spin text-gray-400 text-xl mb-2"></i>
                            <p class="text-xs text-gray-400">جاري التحميل...</p>
                        </div>

                        <!-- Empty -->
                        <div v-else-if="conversations.length === 0"
                            class="flex flex-col items-center justify-center py-16 px-6">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-400">لا توجد محادثات</p>
                            <p class="text-xs text-gray-400 mt-1 text-center">ستظهر المحادثات هنا عندما
                                يبدأ العملاء بالدردشة</p>
                        </div>

                        <!-- Conversations -->
                        <div v-else>
                            <button v-for="conversation in conversations" :key="conversation.session_id"
                                class="w-full flex items-center gap-3 px-4 py-3 text-right cursor-pointer transition-all hover:bg-gray-50 border-b border-gray-50 last:border-0"
                                :class="{ 'bg-emerald-50/40': conversation.unread_count > 0 }"
                                @click.stop="viewConversation(conversation)">

                                <!-- Avatar -->
                                <div class="relative flex-shrink-0">
                                    <div
                                        class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ (getDisplayName(conversation))[0] }}
                                    </div>
                                    <span v-if="conversation.unread_count > 0"
                                        class="absolute -right-1 -bottom-1 w-4 h-4 rounded-full bg-red-500 flex items-center justify-center text-[9px] font-bold text-white ring-2 ring-white">
                                        {{ conversation.unread_count > 9 ? '9+' :
                                            conversation.unread_count }}
                                    </span>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-sm truncate" :class="conversation.unread_count > 0
                                            ? 'font-bold text-gray-800'
                                            : 'font-medium text-gray-600'
                                            ">
                                            {{ getDisplayName(conversation) }}
                                        </h4>
                                        <span class="text-[10px] text-gray-400 flex-shrink-0">
                                            {{ formatTime(conversation.last_message_at) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 truncate mt-0.5"
                                        :class="{ 'font-medium text-gray-600': conversation.unread_count > 0 }">
                                        {{ truncateMessage(conversation.last_message) }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            class="text-[10px] text-gray-400 bg-gray-100 rounded px-1.5 py-0.5">
                                            {{ conversation.messages_count }} رسالة
                                        </span>
                                    </div>
                                </div>

                                <!-- Arrow -->
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-100 bg-gray-50/80 px-4 py-2">
                        <div class="flex items-center justify-center gap-2 text-[10px] text-gray-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            تحديث تلقائي كل 10 ثوانٍ
                        </div>
                    </div>
                </template>
            </div>
        </Transition>
    </div>
</template>

<script setup>
/**
 * LiveChatDropdown — Navbar dropdown for admin live chat
 *
 * Adapted for Vue SPA (no Inertia/Ziggy).
 * Uses project's request wrapper + logger utility.
 */

import { ref, onMounted, onUnmounted, nextTick, inject, watch } from 'vue';
import { getConversations, getConversation, sendReply } from '@/api/livechatApi';
import { registerPollingCallback, unregisterPollingCallback } from '@/services/adminPolling';
import { OPEN_CHAT_TARGET } from '../../dashboardKeys';
import logger from '@/utils/logger';

// No emits — parent does not listen to events from this component

// Injected from DashboardLayout — allows DashboardHome to open a chat
const openChatTarget = inject( OPEN_CHAT_TARGET, () => ref( null ), true );

const isOpen = ref( false );
const conversations = ref( [] );
const unreadCount = ref( 0 );
const isLoading = ref( false );
const selectedConversation = ref( null );
const conversationMessages = ref( [] );
const replyText = ref( '' );
const isSending = ref( false );
const isLoadingConversation = ref( false );
const containerRef = ref( null );
const messagesContainerRef = ref( null );
const inputRef = ref( null );

// Audio context — created after first user interaction
let audioContext = null;
let audioEnabled = false;
const hasNewMessage = ref( false );
let messagePollingInterval = null;

// ─── Audio ───────────────────────────────────────────────
const enableAudio = () =>
{
    if ( audioEnabled ) return;
    try
    {
        audioContext = new ( window.AudioContext || window.webkitAudioContext )();
        if ( audioContext.state === 'suspended' ) audioContext.resume();
        audioEnabled = true;
        document.removeEventListener( 'click', enableAudio );
    } catch ( e )
    {
        logger.warn( '[LiveChat] Could not enable audio:', e.message );
    }
};

const playNotificationSound = () =>
{
    if ( !audioEnabled || !audioContext ) return;
    try
    {
        if ( audioContext.state === 'suspended' ) audioContext.resume();

        const notes = [
            { freq: 830, duration: 0.1 },
            { freq: 988, duration: 0.1 },
            { freq: 1175, duration: 0.15 },
        ];
        let startTime = audioContext.currentTime;
        notes.forEach( ( note ) =>
        {
            const osc = audioContext.createOscillator();
            const gain = audioContext.createGain();
            osc.connect( gain );
            gain.connect( audioContext.destination );
            osc.frequency.value = note.freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime( 0, startTime );
            gain.gain.linearRampToValueAtTime( 0.3, startTime + 0.01 );
            gain.gain.exponentialRampToValueAtTime( 0.01, startTime + note.duration );
            osc.start( startTime );
            osc.stop( startTime + note.duration );
            startTime += note.duration + 0.02;
        } );
    } catch ( error )
    {
        logger.error( '[LiveChat] Error playing notification sound:', error );
    }
};

// ─── Fetch conversations ─────────────────────────────────
const fetchConversations = async () =>
{
    try
    {
        isLoading.value = true;
        const { data } = await getConversations();
        const newConversations = data.conversations || [];
        const newUnreadCount = data.unreadCount || 0;

        if ( newUnreadCount > unreadCount.value )
        {
            playNotificationSound();
            // ✅ Only show pulse dot when dropdown is closed — if open, admin can already see the messages
            if ( !isOpen.value ) hasNewMessage.value = true;
            // (notification state managed internally)
        }

        conversations.value = newConversations;
        unreadCount.value = newUnreadCount;
    } catch ( error )
    {
        logger.error( '[LiveChat] Error fetching conversations:', error );
    } finally
    {
        isLoading.value = false;
    }
};

// ─── Poll messages ───────────────────────────────────────
const pollMessages = async () =>
{
    if ( !selectedConversation.value ) return;

    try
    {
        const { data } = await getConversation( selectedConversation.value.session_id );
        const newMessages = data.messages || [];

        if ( newMessages.length > conversationMessages.value.length )
        {
            const hadMessages = conversationMessages.value.length;
            conversationMessages.value = newMessages;
            if ( hadMessages > 0 ) playNotificationSound();
            await nextTick();
            scrollToBottom();
        }
    } catch ( error )
    {
        logger.error( '[LiveChat] Error polling messages:', error );
    }
};

const scrollToBottom = () =>
{
    if ( messagesContainerRef.value )
    {
        messagesContainerRef.value.scrollTop = messagesContainerRef.value.scrollHeight;
    }
};

// ─── Toggle / Close ──────────────────────────────────────
const toggleDropdown = () =>
{
    isOpen.value = !isOpen.value;
    hasNewMessage.value = false;
    if ( isOpen.value && conversations.value.length === 0 )
    {
        fetchConversations();
    }
};

const closeDropdown = () =>
{
    isOpen.value = false;
    selectedConversation.value = null;
    conversationMessages.value = [];
    replyText.value = '';
    stopMessagePolling();
};

const stopMessagePolling = () =>
{
    if ( messagePollingInterval )
    {
        clearTimeout( messagePollingInterval );
        messagePollingInterval = null;
    }
};

const startMessagePolling = () =>
{
    stopMessagePolling();
    // Non-overlapping setTimeout loop — prevents Chrome violations
    // when pollMessages takes longer than the interval
    const loop = async () =>
    {
        if ( !messagePollingInterval ) return; // stopped
        const started = Date.now();
        try { await pollMessages(); } catch { /* handled inside pollMessages */ }
        if ( !messagePollingInterval ) return; // stopped during poll
        const elapsed = Date.now() - started;
        const nextDelay = Math.max( 5000 - elapsed, 1000 );
        messagePollingInterval = setTimeout( loop, nextDelay );
    };
    messagePollingInterval = setTimeout( loop, 5000 );
};

// ─── View conversation ──────────────────────────────────
const viewConversation = async ( conversation ) =>
{
    selectedConversation.value = conversation;
    replyText.value = '';
    isLoadingConversation.value = true;

    try
    {
        const { data } = await getConversation( conversation.session_id );
        conversationMessages.value = data.messages || [];

        if ( conversation.unread_count > 0 )
        {
            unreadCount.value = Math.max( 0, unreadCount.value - conversation.unread_count );
            conversation.unread_count = 0;
        }

        await nextTick();
        scrollToBottom();
        inputRef.value?.focus();
        startMessagePolling();
    } catch ( error )
    {
        logger.error( '[LiveChat] Error fetching conversation:', error );
    } finally
    {
        isLoadingConversation.value = false;
    }
};

const backToList = () =>
{
    selectedConversation.value = null;
    conversationMessages.value = [];
    replyText.value = '';
    stopMessagePolling();
};

// ─── Send reply ─────────────────────────────────────────
const sendReplyMessage = async () =>
{
    if ( !replyText.value.trim() || !selectedConversation.value ) return;

    const messageText = replyText.value;
    isSending.value = true;

    // Optimistic update
    const tempMessage = {
        id: Date.now(),
        sender: 'admin',
        message: messageText,
        created_at: new Date().toISOString(),
        _pending: true,
    };
    conversationMessages.value.push( tempMessage );
    replyText.value = '';

    await nextTick();
    scrollToBottom();

    try
    {
        const { data } = await sendReply( selectedConversation.value.session_id, messageText );

        const index = conversationMessages.value.findIndex( m => m.id === tempMessage.id );
        if ( index !== -1 )
        {
            conversationMessages.value[ index ] = data.message;
        }

        inputRef.value?.focus();
    } catch ( error )
    {
        logger.error( '[LiveChat] Error sending reply:', error );
        conversationMessages.value = conversationMessages.value.filter( m => m.id !== tempMessage.id );
        replyText.value = messageText;
    } finally
    {
        isSending.value = false;
    }
};

// ─── Click outside ──────────────────────────────────────
const handleClickOutside = ( event ) =>
{
    if ( containerRef.value && !containerRef.value.contains( event.target ) )
    {
        if ( !selectedConversation.value )
        {
            closeDropdown();
        }
    }
};

// ─── Display helpers ────────────────────────────────────
const getDisplayName = ( conversation ) =>
{
    if ( conversation.visitor_name && conversation.visitor_name !== 'زائر' && conversation.visitor_name.trim() )
    {
        return conversation.visitor_name;
    }
    return `زائر ${ conversation.visitor_ip?.split( '.' ).pop() || '' }`;
};

const formatTime = ( date ) =>
{
    if ( !date ) return '';
    const d = new Date( date );
    const diff = Date.now() - d.getTime();
    const mins = Math.floor( diff / 60000 );
    const hours = Math.floor( diff / 3600000 );

    if ( mins < 1 ) return 'الآن';
    if ( mins < 60 ) return `${ mins } د`;
    if ( hours < 24 ) return `${ hours } س`;
    return d.toLocaleTimeString( 'ar-SA', { hour: '2-digit', minute: '2-digit' } );
};

const formatMessageTime = ( date ) =>
{
    if ( !date ) return '';
    return new Date( date ).toLocaleTimeString( 'ar-SA', { hour: '2-digit', minute: '2-digit' } );
};

const truncateMessage = ( message, length = 40 ) =>
{
    if ( !message ) return '';
    return message.length > length ? message.substring( 0, length ) + '...' : message;
};

// ─── WebSocket (optional — if Echo is available) ────────
const setupWebSocket = () =>
{
    if ( typeof window !== 'undefined' && window.Echo )
    {
        try
        {
            window.Echo.private( 'admin-livechat' )
                .listen( '.NewLivechatMessage', ( e ) =>
                {
                    playNotificationSound();
                    // ✅ Only show pulse dot when dropdown is closed
                    if ( !isOpen.value ) hasNewMessage.value = true;
                    fetchConversations();
                    if ( selectedConversation.value?.session_id === e.session_id ) pollMessages();
                    // (notification state managed internally)
                } );
        } catch ( err )
        {
            logger.warn( '[LiveChat] WebSocket setup failed:', err.message );
        }
    }
};

// ─── Lifecycle ──────────────────────────────────────────
onMounted( () =>
{
    document.addEventListener( 'click', handleClickOutside );
    document.addEventListener( 'click', enableAudio, { once: true } );
    fetchConversations();
    setupWebSocket();
    // Use centralized polling instead of independent setInterval
    registerPollingCallback( 'livechat', fetchConversations );
} );

onUnmounted( () =>
{
    unregisterPollingCallback( 'livechat' );
    stopMessagePolling();
    // ✅ Unsubscribe from WS channel to prevent listener accumulation across re-mounts
    if ( typeof window !== 'undefined' && window.Echo ) {
        try { window.Echo.leave( 'admin-livechat' ); } catch { /* safe */ }
    }
    document.removeEventListener( 'click', handleClickOutside );
    document.removeEventListener( 'click', enableAudio );
} );

// ─── External trigger: open chat for a specific customer ─
watch( openChatTarget, async ( customer ) =>
{
    if ( !customer ) return;

    // Open the dropdown
    isOpen.value = true;
    hasNewMessage.value = false;

    // Refresh conversations and find matching one by IP
    await fetchConversations();

    const match = conversations.value.find(
        c => c.visitor_ip === customer.ip || c.visitor_ip === customer.ip_address
    );

    if ( match )
    {
        viewConversation( match );
    }
    else
    {
        logger.debug( '[LiveChat] No conversation found for IP:', customer.ip || customer.ip_address );
    }

    // Reset the trigger
    openChatTarget.value = null;
} );

defineExpose( { fetchConversations, unreadCount, playNotificationSound } );
</script>

<style scoped>
.message-move,
.message-enter-active {
    transition: all 0.3s ease-out;
}

.message-leave-active {
    transition: all 0.2s ease-in;
    position: absolute;
    width: 100%;
}

.message-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.message-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
