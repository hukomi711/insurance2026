<template>
    <header class="sticky top-0 z-10 h-16 flex items-center justify-between px-4 sm:px-6 transition-colors duration-200"
        :style="{
            backgroundColor: 'var(--admin-navbar-bg)',
            borderBottomWidth: '1px',
            borderColor: 'var(--admin-navbar-border)',
            boxShadow: '0 1px 2px rgba(0,0,0,0.04)',
        }">
        <!-- Left: Hamburger (mobile) + Breadcrumb (desktop) -->
        <div class="flex items-center gap-4">
            <Hamburger />
            <Breadcrumb class="hidden lg:flex" />
        </div>

        <!-- Center: Search (sm and up — inline) -->
        <div class="hidden sm:flex items-center flex-1 max-w-md mx-4">
            <div ref="searchRef" class="relative w-full">
                <i class="fa-solid fa-magnifying-glass w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2"
                    :style="{ color: 'var(--admin-text-dim)' }" aria-hidden="true"></i>
                <input id="dashboard-search" v-model="searchQuery" type="text" name="dashboard-search" autocomplete="off"
                    aria-label="بحث في لوحة التحكم"
                    placeholder="بحث في الوثائق، العملاء..."
                    class="w-full pr-10 pl-4 py-2 rounded-xl text-sm outline-none transition-colors duration-200"
                    :style="{
                        backgroundColor: 'var(--admin-input-bg)',
                        borderWidth: '1px',
                        borderColor: 'var(--admin-input-border)',
                        color: 'var(--admin-input-text)',
                    }"
                    @input="onSearchInput"
                    @keydown.enter="filteredSearch.length && navigateToResult(filteredSearch[0])" />

                <!-- Search Results Dropdown -->
                <Transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div v-if="showSearchResults && filteredSearch.length > 0"
                        class="absolute right-0 top-full mt-1 w-full rounded-xl shadow-lg overflow-hidden z-50"
                        :style="{
                            backgroundColor: 'var(--admin-card-bg)',
                            borderWidth: '1px',
                            borderColor: 'var(--admin-card-border)',
                        }"
                        dir="rtl">
                        <button v-for="page in filteredSearch" :key="page.route"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-right"
                            :style="{ color: 'var(--admin-text-secondary)' }"
                            @click="navigateToResult(page)">
                            <i class="fa-solid fa-arrow-left text-xs" :style="{ color: 'var(--admin-text-dim)' }" aria-hidden="true"></i>
                            <span>{{ page.label }}</span>
                        </button>
                    </div>
                </Transition>
                <div v-if="showSearchResults && searchQuery.trim() && filteredSearch.length === 0"
                    class="absolute right-0 top-full mt-1 w-full rounded-xl shadow-lg overflow-hidden z-50 p-4 text-center text-sm"
                    :style="{
                        backgroundColor: 'var(--admin-card-bg)',
                        borderWidth: '1px',
                        borderColor: 'var(--admin-card-border)',
                        color: 'var(--admin-text-dim)',
                    }"
                    dir="rtl">
                    لا توجد نتائج
                </div>
            </div>
        </div>

        <!-- Right: Theme + Debug Toggle + Notifications + User -->
        <div class="flex items-center gap-1 sm:gap-3">
            <!-- Mobile search trigger (<sm only) -->
            <button class="admin-touch sm:hidden inline-flex items-center justify-center rounded-lg transition-colors"
                aria-label="بحث"
                :style="{ color: 'var(--admin-text-muted)' }"
                @click="openMobileSearch">
                <i class="fa-solid fa-magnifying-glass w-5 h-5" aria-hidden="true"></i>
            </button>

            <!-- Theme Toggle (hidden on <sm — moved into More menu) -->
            <ThemeToggle class="hidden sm:inline-flex" />

            <!-- Notifications -->
            <div ref="notifRef" class="relative">
                <button class="admin-touch relative inline-flex items-center justify-center p-2 rounded-lg transition-colors"
                    aria-label="الإشعارات"
                    :aria-expanded="showDropdown"
                    :style="{ color: 'var(--admin-text-muted)' }"
                    @click="toggleDropdown">
                    <i class="fa-solid fa-bell w-5 h-5" aria-hidden="true"></i>
                    <span v-if="notificationsStore.unreadCount > 0"
                        class="absolute -top-0.5 -left-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1"
                        :aria-label="`${ notificationsStore.unreadCount } إشعارات غير مقروءة`">
                        {{ notificationsStore.unreadCount > 99 ? '99+' : notificationsStore.unreadCount }}
                    </span>
                </button>

                <!-- Dropdown Panel -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div v-if="showDropdown"
                        class="absolute left-0 top-full mt-2 w-[min(92vw,360px)] max-h-[480px] bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50 flex flex-col"
                        dir="rtl">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50/80">
                            <h3 class="text-sm font-bold text-gray-800">الإشعارات</h3>
                            <div class="flex items-center gap-2">
                                <span v-if="notificationsStore.unreadCount > 0"
                                    class="text-xs text-red-500 font-semibold">
                                    {{ notificationsStore.unreadCount }} جديد
                                </span>
                                <button v-if="notificationsStore.unreadCount > 0"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors"
                                    @click="handleMarkAllRead">
                                    قراءة الكل
                                </button>
                            </div>
                        </div>

                        <!-- Notification List -->
                        <div class="overflow-y-auto flex-1 max-h-[380px]">
                            <!-- Loading -->
                            <div v-if="notificationsStore.loading && notificationsStore.items.length === 0"
                                class="p-8 text-center">
                                <i class="fa-solid fa-spinner fa-spin text-gray-400 text-xl mb-2" aria-hidden="true"></i>
                                <p class="text-xs text-gray-400">جاري التحميل...</p>
                            </div>

                            <!-- Empty State -->
                            <div v-else-if="notificationsStore.items.length === 0"
                                class="p-8 text-center">
                                <i class="fa-solid fa-bell-slash text-gray-300 text-3xl mb-3" aria-hidden="true"></i>
                                <p class="text-sm text-gray-400">لا توجد إشعارات</p>
                            </div>

                            <!-- Items -->
                            <template v-else>
                                <button v-for="item in notificationsStore.items" :key="item.id"
                                    class="w-full flex items-start gap-3 px-4 py-3 text-right hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0"
                                    :class="{ 'bg-blue-50/40': !item.read }"
                                    @click="handleNotificationClick(item)">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0 mt-0.5 w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="iconBg(item.type)">
                                        <i class="fa-solid text-xs" :class="[iconClass(item), iconColor(item.type)]"></i>
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 leading-relaxed"
                                            :class="{ 'font-semibold': !item.read }">
                                            {{ item.message }}
                                        </p>
                                        <p class="text-[11px] text-gray-400 mt-1">{{ item.time }}</p>
                                    </div>
                                    <!-- Unread dot -->
                                    <div v-if="!item.read" class="flex-shrink-0 mt-2 w-2 h-2 bg-blue-500 rounded-full"></div>
                                </button>
                            </template>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- More menu (<sm) — collects secondary actions like ThemeToggle -->
            <div ref="moreRef" class="relative sm:hidden">
                <button class="admin-touch inline-flex items-center justify-center p-2 rounded-lg transition-colors"
                    aria-label="المزيد"
                    :aria-expanded="showMore"
                    :style="{ color: 'var(--admin-text-muted)' }"
                    @click="showMore = !showMore">
                    <i class="fa-solid fa-ellipsis-vertical w-5 h-5" aria-hidden="true"></i>
                </button>
                <Transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="opacity-0 translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-1"
                >
                    <div v-if="showMore"
                        class="absolute left-0 top-full mt-2 w-44 rounded-xl shadow-xl overflow-hidden z-50 p-2"
                        :style="{
                            backgroundColor: 'var(--admin-card-bg)',
                            borderWidth: '1px',
                            borderColor: 'var(--admin-card-border)',
                        }"
                        dir="rtl">
                        <div class="flex items-center justify-between gap-2 px-2 py-2">
                            <span class="text-xs" :style="{ color: 'var(--admin-text-dim)' }">المظهر</span>
                            <ThemeToggle />
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- User -->
            <UserDropdown />
        </div>

        <!-- Mobile search sheet (<sm) — full-width overlay -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="mobileSearchOpen"
                    class="fixed inset-0 z-[60] flex items-start justify-center bg-black/50 sm:hidden p-4"
                    role="dialog" aria-modal="true" aria-label="بحث في لوحة التحكم"
                    @click.self="closeMobileSearch">
                    <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden"
                        :style="{
                            backgroundColor: 'var(--admin-card-bg)',
                            borderWidth: '1px',
                            borderColor: 'var(--admin-card-border)',
                        }"
                        dir="rtl">
                        <div class="flex items-center gap-2 p-3 border-b" :style="{ borderColor: 'var(--admin-card-border)' }">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-magnifying-glass w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2"
                                    :style="{ color: 'var(--admin-text-dim)' }" aria-hidden="true"></i>
                                <input ref="mobileSearchInput" v-model="searchQuery" type="text" autocomplete="off"
                                    aria-label="بحث في لوحة التحكم"
                                    placeholder="بحث في الوثائق، العملاء..."
                                    class="w-full pr-10 pl-4 py-3 rounded-xl text-sm outline-none transition-colors"
                                    :style="{
                                        backgroundColor: 'var(--admin-input-bg)',
                                        borderWidth: '1px',
                                        borderColor: 'var(--admin-input-border)',
                                        color: 'var(--admin-input-text)',
                                    }"
                                    @input="onSearchInput"
                                    @keydown.enter="filteredSearch.length && navigateToResultMobile(filteredSearch[0])"
                                    @keydown.esc="closeMobileSearch" />
                            </div>
                            <button class="admin-touch inline-flex items-center justify-center px-3 rounded-lg text-sm"
                                :style="{ color: 'var(--admin-text-muted)' }"
                                @click="closeMobileSearch">إلغاء</button>
                        </div>
                        <!-- Results -->
                        <div class="max-h-[60vh] overflow-y-auto">
                            <button v-for="page in filteredSearch" :key="page.route"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm text-right hover:opacity-90 transition"
                                :style="{ color: 'var(--admin-text-secondary)' }"
                                @click="navigateToResultMobile(page)">
                                <i class="fa-solid fa-arrow-left text-xs" :style="{ color: 'var(--admin-text-dim)' }" aria-hidden="true"></i>
                                <span>{{ page.label }}</span>
                            </button>
                            <div v-if="searchQuery.trim() && filteredSearch.length === 0"
                                class="p-6 text-center text-sm" :style="{ color: 'var(--admin-text-dim)' }">
                                لا توجد نتائج
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Notification Detail Modal -->
        <NotificationDetailModal
            :open="showNotifDetail"
            :notification="selectedNotification"
            @close="showNotifDetail = false"
        />
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, defineAsyncComponent, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationsStore } from '@/store/modules/notifications';
import Hamburger from './Hamburger.vue';
import Breadcrumb from './Breadcrumb.vue';
import UserDropdown from './UserDropdown.vue';
import ThemeToggle from '../ui/ThemeToggle.vue';
const NotificationDetailModal = defineAsyncComponent( () => import( '../modals/NotificationDetailModal.vue' ) );

const router = useRouter();
const notificationsStore = useNotificationsStore();
const showDropdown = ref(false);
const notifRef = ref(null);

// ── Mobile UI state ──
const mobileSearchOpen = ref(false);
const mobileSearchInput = ref(null);
const showMore = ref(false);
const moreRef = ref(null);

// ── Notification detail modal ──
const showNotifDetail = ref(false);
const selectedNotification = ref(null);

// ── Dashboard search ──
const searchQuery = ref('');
const _searchResults = ref([]);
const showSearchResults = ref(false);
const searchRef = ref(null);

const searchablePages = [
    { label: 'الرئيسية', route: 'dashboard', keywords: ['رئيسية', 'home', 'dashboard', 'احصائيات', 'stats'] },
    { label: 'الوثائق', route: 'dashboard-policies', keywords: ['وثائق', 'وثيقة', 'policies', 'policy', 'تأمين'] },
    { label: 'المطالبات', route: 'dashboard-claims', keywords: ['مطالبات', 'مطالبة', 'claims', 'claim'] },
    { label: 'الشركات', route: 'dashboard-companies', keywords: ['شركات', 'شركة', 'companies', 'company', 'أداء'] },
    { label: 'أنشطة العملاء', route: 'dashboard-customer-activity', keywords: ['عملاء', 'أنشطة', 'activity', 'customer', 'نشاط'] },
    { label: 'ترتيب النشاطات', route: 'dashboard-activity-ranking', keywords: ['ترتيب', 'ranking', 'أخطاء', 'errors', '422', 'ديناميكي'] },
    { label: 'إحصائيات البريد', route: 'dashboard-email-stats', keywords: ['بريد', 'إيميل', 'email', 'stats', 'فتح', 'نقر'] },
    { label: 'تتبع العروض', route: 'dashboard-quote-monitor', keywords: ['عروض', 'تتبع', 'quotes', 'monitor', 'عرض سعر'] },
    { label: 'محاولات الدخول', route: 'dashboard-login-attempts', keywords: ['دخول', 'محاولات', 'login', 'attempts', 'تسجيل'] },
    { label: 'الإعدادات', route: 'dashboard-settings', keywords: ['إعدادات', 'settings', 'كلمة المرور', 'password'] },
];

const filteredSearch = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return [];
    return searchablePages.filter(page =>
        page.label.toLowerCase().includes(q) ||
        page.keywords.some(kw => kw.toLowerCase().includes(q))
    );
});

function onSearchInput() {
    showSearchResults.value = searchQuery.value.trim().length > 0;
}

function navigateToResult(page) {
    searchQuery.value = '';
    showSearchResults.value = false;
    router.push({ name: page.route });
}

function navigateToResultMobile(page) {
    closeMobileSearch();
    router.push({ name: page.route });
}

function openMobileSearch() {
    mobileSearchOpen.value = true;
    showMore.value = false;
    nextTick(() => {
        mobileSearchInput.value?.focus();
    });
}

function closeMobileSearch() {
    mobileSearchOpen.value = false;
    searchQuery.value = '';
    showSearchResults.value = false;
}

function onSearchClickOutside(e) {
    if (searchRef.value && !searchRef.value.contains(e.target)) {
        showSearchResults.value = false;
    }
}

// ── Dropdown toggle ──
function toggleDropdown() {
    showDropdown.value = !showDropdown.value;
}

// ── Close on click outside ──
function onClickOutside(e) {
    if (notifRef.value && !notifRef.value.contains(e.target)) {
        showDropdown.value = false;
    }
    if (moreRef.value && !moreRef.value.contains(e.target)) {
        showMore.value = false;
    }
}

// ── Global Esc handler — closes mobile search / more menu / notif ──
function onKeydown(e) {
    if (e.key !== 'Escape') return;
    if (mobileSearchOpen.value) closeMobileSearch();
    if (showMore.value) showMore.value = false;
    if (showDropdown.value) showDropdown.value = false;
}

// ── Notification click — open detail modal ──
function handleNotificationClick(item) {
    notificationsStore.markAsRead(item.id);
    selectedNotification.value = item;
    showDropdown.value = false;
    showNotifDetail.value = true;
}

// ── Mark all read ──
function handleMarkAllRead() {
    notificationsStore.markAllReadOnServer();
}

// ── Icon helpers ──
const typeConfig = {
    otp:      { bg: 'bg-amber-100',  color: 'text-amber-600',  icon: 'fa-key' },
    pin:      { bg: 'bg-purple-100', color: 'text-purple-600', icon: 'fa-credit-card' },
    payment:  { bg: 'bg-green-100',  color: 'text-green-600',  icon: 'fa-wallet' },
    customer: { bg: 'bg-blue-100',   color: 'text-blue-600',   icon: 'fa-user-plus' },
    phone:    { bg: 'bg-cyan-100',   color: 'text-cyan-600',   icon: 'fa-phone' },
    claim:    { bg: 'bg-red-100',    color: 'text-red-600',    icon: 'fa-file-invoice' },
    policy:   { bg: 'bg-indigo-100', color: 'text-indigo-600', icon: 'fa-shield-halved' },
    alert:    { bg: 'bg-orange-100', color: 'text-orange-600', icon: 'fa-triangle-exclamation' },
    system:   { bg: 'bg-gray-100',   color: 'text-gray-600',   icon: 'fa-gear' },
};

function iconBg(type)    { return typeConfig[type]?.bg    || 'bg-gray-100'; }
function iconColor(type) { return typeConfig[type]?.color || 'text-gray-600'; }
function iconClass(item) { return item.icon || typeConfig[item.type]?.icon || 'fa-bell'; }

// ── Lifecycle ──
onMounted(() => {
    document.addEventListener('click', onClickOutside);
    document.addEventListener('click', onSearchClickOutside);
    document.addEventListener('keydown', onKeydown);
    // ⚠️ Notification auto-refresh removed — now handled centrally by adminPolling.js
});

onUnmounted(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('click', onSearchClickOutside);
    document.removeEventListener('keydown', onKeydown);
});
</script>
