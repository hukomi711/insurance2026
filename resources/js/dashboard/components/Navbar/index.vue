<template>
    <header class="admin-navbar sticky top-0 z-10 flex h-16 items-center justify-between gap-2 px-3 transition-colors duration-200 sm:px-4 lg:px-6"
        :style="{
            backgroundColor: 'var(--admin-navbar-bg)',
            borderBottomWidth: '1px',
            borderColor: 'var(--admin-navbar-border)',
            boxShadow: '0 1px 2px rgba(0,0,0,0.04)',
        }">
        <!-- Left: Hamburger (mobile) + Breadcrumb (desktop) -->
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3 lg:gap-4">
            <Hamburger />
            <div class="hidden min-w-0 md:block lg:hidden">
                <p class="admin-navbar-page-title truncate text-sm font-semibold">
                    {{ currentPageTitle }}
                </p>
            </div>
            <Breadcrumb class="hidden lg:flex" />
        </div>

        <!-- Right: Theme + Debug Toggle + Notifications + User -->
        <div class="flex min-w-0 shrink-0 items-center gap-1.5 sm:gap-2 lg:gap-3">
            <!-- Search trigger (tablet + desktop) -->
            <button class="admin-touch hidden md:inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                aria-label="بحث"
                :style="{ color: 'var(--admin-text-muted)' }"
                @click="openMobileSearch">
                <i class="fa-solid fa-magnifying-glass w-5 h-5" aria-hidden="true"></i>
            </button>

            <!-- Theme Toggle (desktop — mobile/tablet moved into More menu) -->
            <ThemeToggle class="hidden lg:inline-flex" />

            <button
                type="button"
                class="admin-touch inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                :class="soundsReady ? 'bg-emerald-500/15 text-emerald-600' : ''"
                :style="soundsReady ? {} : { color: 'var(--admin-text-muted)' }"
                :aria-label="soundsReady ? 'إيقاف التنبيهات الصوتية' : 'تفعيل التنبيهات الصوتية'"
                :aria-pressed="soundsReady"
                :title="soundsReady ? 'التنبيهات الصوتية مفعّلة' : 'تفعيل التنبيهات الصوتية'"
                data-admin-sound-toggle
                @click="handleSoundToggle"
            >
                <i class="fa-solid" :class="soundsReady ? 'fa-volume-high' : 'fa-volume-xmark'" aria-hidden="true"></i>
            </button>

            <!-- User Management (admin / super_admin only) -->
            <button v-if="userStore.canManageDashboard"
                type="button"
                class="admin-touch hidden lg:inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                aria-label="إدارة المستخدمين"
                title="إدارة المستخدمين"
                :style="{ color: 'var(--admin-text-muted)' }"
                @click="showUserManagement = true">
                <i class="fa-solid fa-users-gear w-5 h-5" aria-hidden="true"></i>
            </button>

            <!-- Notifications -->
            <div ref="notifRef" class="relative">
                <button class="admin-touch relative inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                    aria-label="الإشعارات"
                    :aria-expanded="showDropdown"
                    aria-haspopup="dialog"
                    aria-controls="admin-notifications-panel"
                    :style="{ color: 'var(--admin-text-muted)' }"
                    @click="toggleDropdown">
                    <i class="fa-solid fa-bell w-5 h-5" aria-hidden="true"></i>
                    <span v-if="notificationsStore.unreadCount > 0"
                        class="absolute -inset-e-0.5 -top-0.5 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
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
                    <div v-if="showDropdown" id="admin-notifications-panel"
                        class="absolute left-0 top-full mt-2 w-[min(92vw,360px)] max-h-120 rounded-xl shadow-xl border overflow-hidden z-50 flex flex-col"
                        :style="{
                            backgroundColor: 'var(--admin-card-bg)',
                            borderColor: 'var(--admin-card-border)',
                        }"
                        dir="rtl" role="dialog" aria-label="مركز الإشعارات">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 py-3 border-b"
                            :style="{ borderColor: 'var(--admin-card-border)', backgroundColor: 'var(--admin-surface-2)' }">
                            <h3 class="text-sm font-bold" :style="{ color: 'var(--admin-text)' }">الإشعارات</h3>
                            <div class="flex items-center gap-2">
                                <span v-if="notificationsStore.unreadCount > 0"
                                    class="text-xs text-red-500 font-semibold">
                                    {{ notificationsStore.unreadCount }} جديد
                                </span>
                                <button
                                    class="admin-touch inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-blue-700 hover:bg-blue-50 transition-colors disabled:opacity-50"
                                    :disabled="notificationsStore.loading"
                                    aria-label="تحديث الإشعارات"
                                    title="تحديث الإشعارات"
                                    @click.stop="refreshNotifications">
                                    <i class="fa-solid text-xs"
                                        :class="notificationsStore.loading ? 'fa-spinner fa-spin' : 'fa-rotate'"></i>
                                </button>
                                <button v-if="notificationsStore.hasUnread"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors disabled:opacity-50"
                                    :disabled="notificationsStore.markingAllRead"
                                    @click.stop="handleMarkAllRead">
                                    {{ notificationsStore.markingAllRead ? 'جاري الحفظ...' : 'قراءة الكل' }}
                                </button>
                            </div>
                        </div>
                        <div v-if="notificationsStore.loading && notificationsStore.items.length > 0"
                            class="h-0.5 bg-blue-100 overflow-hidden">
                            <div class="h-full w-1/2 bg-blue-500 animate-pulse"></div>
                        </div>

                        <!-- Notification List -->
                        <div class="overflow-y-auto flex-1 max-h-95">
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
                                <button v-for="item in notificationsStore.items" :key="notificationKey(item)"
                                    class="w-full flex items-start gap-3 px-4 py-3 text-right hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0"
                                    :class="{ 'bg-blue-50/40': !item.read }"
                                    @click="handleNotificationClick(item)">
                                    <!-- Icon -->
                                    <div class="shrink-0 mt-0.5 w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="iconBg(item.type)">
                                        <i class="fa-solid text-xs" :class="[iconClass(item), iconColor(item.type)]"></i>
                                    </div>
                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 leading-relaxed"
                                            :class="{ 'font-semibold': !item.read }">
                                            {{ item.message }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <p class="text-[11px] text-gray-400"
                                                :title="formatNotificationDate(item)">
                                                {{ displayNotificationTime(item) }}
                                            </p>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                                {{ typeLabel(item.type) }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Unread dot -->
                                    <div v-if="!item.read" class="shrink-0 mt-2 w-2 h-2 bg-blue-500 rounded-full"></div>
                                </button>
                            </template>
                        </div>

                        <div v-if="notificationsStore.error || notificationsStore.lastFetchedAt"
                            class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-[11px]">
                            <span v-if="notificationsStore.error" class="text-red-500">
                                {{ notificationsStore.error }}
                            </span>
                            <span v-else class="text-gray-400">
                                آخر تحديث: {{ lastFetchedLabel }}
                            </span>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- More menu (<lg) — collects secondary actions -->
            <div ref="moreRef" class="relative lg:hidden">
                <button class="admin-touch inline-flex h-10 w-10 items-center justify-center rounded-xl transition-colors"
                    aria-label="المزيد"
                    :aria-expanded="showMore"
                    aria-haspopup="dialog"
                    aria-controls="admin-more-menu"
                    :style="{ color: 'var(--admin-text-muted)' }"
                    @click="toggleMoreMenu">
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
                    <div v-if="showMore" id="admin-more-menu" role="dialog" aria-label="المزيد من خيارات لوحة التحكم"
                        class="absolute left-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-2xl p-2 shadow-xl"
                        :style="{
                            backgroundColor: 'var(--admin-card-bg)',
                            borderWidth: '1px',
                            borderColor: 'var(--admin-card-border)',
                        }"
                        dir="rtl">
                        <div class="md:hidden">
                            <button
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-black/5"
                                :style="{ color: 'var(--admin-text-secondary)' }"
                                @click="openMobileSearch">
                                <i class="fa-solid fa-magnifying-glass w-4 text-center text-[13px]" aria-hidden="true"></i>
                                <span>البحث</span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors hover:bg-black/5">
                            <span :style="{ color: 'var(--admin-text-secondary)' }">الوضع الفاتح/الداكن</span>
                            <ThemeToggle />
                        </div>

                        <div class="my-2 h-px bg-black/5"></div>

                        <button
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-black/5"
                            :style="{ color: 'var(--admin-text-secondary)' }"
                            @click="openSettings">
                            <i class="fa-solid fa-gear w-4 text-center text-[13px]" aria-hidden="true"></i>
                            <span>الإعدادات</span>
                        </button>

                        <button v-if="userStore.canManageDashboard"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-black/5"
                            :style="{ color: 'var(--admin-text-secondary)' }"
                            @click="openUserManagementFromMenu">
                            <i class="fa-solid fa-users-gear w-4 text-center text-[13px]" aria-hidden="true"></i>
                            <span>إدارة المستخدمين</span>
                        </button>

                        <button
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-black/5"
                            :style="{ color: 'var(--admin-text-secondary)' }"
                            @click="openProfile">
                            <i class="fa-solid fa-user w-4 text-center text-[13px]" aria-hidden="true"></i>
                            <span>الملف الشخصي</span>
                        </button>

                        <button
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-black/5"
                            :style="{ color: 'var(--admin-text-secondary)' }"
                            @click="handleLogout">
                            <i class="fa-solid fa-right-from-bracket w-4 text-center text-[13px]" aria-hidden="true"></i>
                            <span>تسجيل الخروج</span>
                        </button>
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
                    class="fixed inset-0 z-60 flex items-start justify-center bg-black/50 p-4"
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
                                <input id="dashboard-mobile-search" ref="mobileSearchInput" v-model="searchQuery" type="text" name="dashboard-mobile-search" autocomplete="off"
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

        <!-- User Management Modal -->
        <UserManagementModal
            v-if="userStore.canManageDashboard"
            :open="showUserManagement"
            @close="showUserManagement = false"
        />
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, defineAsyncComponent, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationsStore } from '@/store/modules/notifications';
import { useUserStore } from '@/store/modules/user';
import { useAdminSounds } from '@/dashboard/composables/useAdminSounds';
import Hamburger from './Hamburger.vue';
import Breadcrumb from './Breadcrumb.vue';
import UserDropdown from './UserDropdown.vue';
import ThemeToggle from '../ui/ThemeToggle.vue';
const NotificationDetailModal = defineAsyncComponent( () => import( '../modals/NotificationDetailModal.vue' ) );
const UserManagementModal = defineAsyncComponent( () => import( '../modals/UserManagementModal.vue' ) );

const router = useRouter();
const route = router.currentRoute;
const notificationsStore = useNotificationsStore();
const userStore = useUserStore();
const { soundsReady, toggleSounds, playNewData } = useAdminSounds();
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

// ── User management modal ──
const showUserManagement = ref(false);

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
    { label: 'إحصائيات البريد', route: 'dashboard-email-stats', keywords: ['بريد', 'إيميل', 'email', 'stats', 'فتح', 'نقر'] },
    { label: 'تتبع العروض', route: 'dashboard-quote-monitor', keywords: ['عروض', 'تتبع', 'quotes', 'monitor', 'عرض سعر'] },
    { label: 'محاولات الدخول', route: 'dashboard-login-attempts', keywords: ['دخول', 'محاولات', 'login', 'attempts', 'تسجيل'] },
    { label: 'الإعدادات', route: 'dashboard-settings', keywords: ['إعدادات', 'settings', 'كلمة المرور', 'password'] },
];

const currentPageTitle = computed(() => {
    const matched = route.value?.matched?.filter((record) => record.meta?.title) || [];
    return matched.at(-1)?.meta?.title || 'لوحة التحكم';
});

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

function navigateToResultMobile(page) {
    closeMobileSearch();
    router.push({ name: page.route });
}

function openMobileSearch() {
    mobileSearchOpen.value = true;
    showMore.value = false;
    showDropdown.value = false;
    nextTick(() => {
        mobileSearchInput.value?.focus();
    });
}

function closeMoreMenu() {
    showMore.value = false;
}

function toggleMoreMenu() {
    showMore.value = !showMore.value;
    if ( showMore.value ) showDropdown.value = false;
}

async function handleSoundToggle() {
    const enabled = await toggleSounds();
    if ( enabled ) await playNewData();
}

function openSettings() {
    closeMoreMenu();
    router.push({ name: 'dashboard-settings', query: { section: 'general' } });
}

function openUserManagementFromMenu() {
    closeMoreMenu();
    showUserManagement.value = true;
}

function openProfile() {
    closeMoreMenu();
    router.push({ name: 'dashboard-settings', query: { section: 'account' } });
}

async function handleLogout() {
    closeMoreMenu();
    await userStore.logout();
    router.push({ name: 'login' });
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
    if (showDropdown.value) {
        showMore.value = false;
        notificationsStore.fetchNotifications();
    }
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

const typeLabels = {
    otp: 'OTP',
    pin: 'PIN',
    payment: 'دفع',
    customer: 'عميل',
    phone: 'هاتف',
    claim: 'مطالبة',
    policy: 'وثيقة',
    alert: 'تنبيه',
    system: 'نظام',
};

const lastFetchedLabel = computed(() => {
    if (!notificationsStore.lastFetchedAt) return '';
    return new Date(notificationsStore.lastFetchedAt).toLocaleTimeString('ar-SA', {
        hour: '2-digit',
        minute: '2-digit',
    });
});

function iconBg(type)    { return typeConfig[type]?.bg    || 'bg-gray-100'; }
function iconColor(type) { return typeConfig[type]?.color || 'text-gray-600'; }
function iconClass(item) { return item.icon || typeConfig[item.type]?.icon || 'fa-bell'; }
function typeLabel(type) { return typeLabels[type] || 'إشعار'; }
function notificationKey(item) { return item.key || `${ item.type }-${ item.meta?.otp_id || item.meta?.card_id || item.meta?.customer_id || item.id }`; }
function displayNotificationTime(item) { return item.time || formatNotificationDate(item) || 'الآن'; }
function formatNotificationDate(item) {
    if (!item.created_at) return item.time || '';
    return new Date(item.created_at).toLocaleString('ar-SA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function refreshNotifications() {
    notificationsStore.fetchNotifications();
}

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
