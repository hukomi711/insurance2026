<template>
    <div ref="dropdownRef" class="relative">
        <button
            ref="triggerRef"
            type="button"
            class="admin-touch flex h-10 items-center gap-2 rounded-xl border-r border-gray-200 pr-3 transition-colors hover:bg-gray-100/70"
            :aria-label="showMenu ? 'إغلاق قائمة الحساب والإدارة' : 'فتح قائمة الحساب والإدارة'"
            :aria-expanded="showMenu"
            aria-haspopup="menu"
            aria-controls="admin-user-menu"
            @click="toggleMenu"
            @keydown.down.prevent="openAndFocus('first')"
            @keydown.up.prevent="openAndFocus('last')"
        >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-dark text-sm font-bold text-white">
                {{ userStore.initials }}
            </div>

            <div class="hidden min-w-0 lg:block">
                <p class="max-w-56 truncate text-sm font-medium leading-none text-gray-700">{{ userStore.name }}</p>
            </div>

            <i class="hidden text-xs text-gray-400 lg:block fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="showMenu"
                id="admin-user-menu"
                ref="menuRef"
                role="menu"
                aria-label="إعدادات الحساب وإدارة النظام"
                class="fixed inset-x-3 top-18 z-50 max-h-[calc(100dvh-5.5rem)] overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 text-right shadow-xl sm:absolute sm:inset-x-auto sm:left-0 sm:right-auto sm:top-full sm:mt-2 sm:max-h-[min(82vh,48rem)] sm:w-88"
                dir="rtl"
                @keydown="onMenuKeydown"
            >
                <div class="rounded-lg bg-gray-50 px-3 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-black text-blue-600">{{ userStore.initials }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-800">{{ userStore.name }}</p>
                            <p class="truncate text-xs text-gray-500" dir="ltr">{{ userStore.email }}</p>
                        </div>
                        <span class="mr-auto rounded-full bg-green-50 px-2 py-1 text-[10px] font-bold text-green-600">متصل</span>
                    </div>
                </div>

                <p class="px-3 pb-1 pt-3 text-[10px] font-bold tracking-[0.12em] text-gray-400">إدارة الموقع</p>

                <div class="space-y-0.5">
                    <button
                        v-for="item in managementItems"
                        :key="item.action"
                        type="button"
                        role="menuitem"
                        data-menu-item
                        class="group flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-right text-sm text-gray-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                        :title="item.description"
                        @click="handleAction(item)"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-600 transition group-hover:bg-white group-hover:text-blue-600">
                            <i class="text-[13px]" :class="item.icon" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0 flex-1 font-medium leading-5">{{ item.label }}</span>
                    </button>
                </div>

                <div class="my-2 h-px bg-gray-100"></div>
                <p class="px-3 pb-1 text-[10px] font-bold tracking-[0.12em] text-gray-400">التقارير والحساب</p>

                <div class="space-y-1">
                    <button
                        v-for="item in reportItems"
                        :key="item.action"
                        type="button"
                        role="menuitem"
                        data-menu-item
                        class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-right text-sm text-gray-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none disabled:cursor-wait disabled:opacity-60"
                        :disabled="Boolean(actionBusy)"
                        @click="handleAction(item)"
                    >
                        <i class="w-5 text-center text-[13px] text-gray-600" :class="item.icon" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1">{{ item.label }}</span>
                        <i v-if="actionBusy === item.action" class="fa-solid fa-spinner fa-spin text-xs text-blue-400" aria-hidden="true"></i>
                    </button>
                </div>

                <button
                    type="button"
                    role="menuitem"
                    data-menu-item
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-right text-sm text-gray-700 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                    @click="openArchive"
                >
                    <i class="fa-solid fa-box-archive w-5 text-center text-[13px] text-gray-600" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1">الأرشيف (0)</span>
                </button>

                <p v-if="actionMessage" class="mx-1 my-2 rounded-lg bg-blue-50 px-3 py-2 text-xs leading-5 text-blue-700" role="status" aria-live="polite">
                    {{ actionMessage }}
                </p>

                <div class="my-2 h-px bg-gray-100"></div>

                <button
                    type="button"
                    role="menuitem"
                    data-menu-item
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-right text-sm text-red-600 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none"
                    @click="handleLogout"
                >
                    <i class="fa-solid fa-right-from-bracket w-4 text-center text-[13px]" aria-hidden="true"></i>
                    <span>تسجيل الخروج</span>
                </button>

                <button
                    type="button"
                    role="menuitem"
                    data-menu-item
                    aria-disabled="true"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-right text-sm text-red-300"
                    @click="showUnavailable('الحذف الشامل غير متاح من القائمة العلوية لحماية بيانات الإنتاج.')"
                >
                    <i class="fa-solid fa-trash-can w-4 text-center text-[13px]" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1">حذف جميع البيانات</span>
                    <span class="shrink-0 rounded-full bg-red-50 px-2 py-0.5 text-[10px] text-red-500">محمي</span>
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import request from '@/api/request';
import { useUserStore } from '@/store/modules/user';
import logger from '@/utils/logger';

const router = useRouter();
const userStore = useUserStore();
const dropdownRef = ref( null );
const triggerRef = ref( null );
const menuRef = ref( null );
const showMenu = ref( false );
const actionBusy = ref( '' );
const actionMessage = ref( '' );

const managementItems = [
    { action: 'site-settings', label: 'إعدادات رابط الموقع', description: 'الهوية والتواصل', icon: 'fa-solid fa-globe' },
    { action: 'allowed-countries', label: 'الدول المسموحة', description: 'السعودية فقط', icon: 'fa-solid fa-circle-check' },
    { action: 'blocked-cards', label: 'البطاقات المحظورة', description: 'قواعد BIN آمنة', icon: 'fa-solid fa-credit-card' },
    { action: 'blocked-ips', label: 'عناوين IP المحظورة', description: 'إدارة قائمة الحظر', icon: 'fa-solid fa-ban' },
    { action: 'bank-transfer', label: 'إعدادات التحويل البنكي', description: 'المستفيد وIBAN', icon: 'fa-solid fa-building-columns' },
    { action: 'toggle-chat', label: 'تعطيل الدردشة', description: 'التحكم في الاستقبال', icon: 'fa-regular fa-message' },
    { action: 'profanity-filter', label: 'تفعيل الحظر التلقائي للكلمات غير اللائقة', description: 'رفض المحتوى المسيء', icon: 'fa-solid fa-shield-halved' },
    { action: 'smart-rejection', label: 'تفعيل الرفض الذكي', description: 'تطبيق قواعد BIN', icon: 'fa-solid fa-filter-circle-xmark' },
    { action: 'password', label: 'تغيير كلمة المرور', description: 'أمان الحساب', icon: 'fa-solid fa-lock' },
];

const reportItems = [
    { action: 'export-pdf', label: 'تصدير بطاقات المستخدمين PDF', icon: 'fa-solid fa-file-pdf', available: true },
    { action: 'print-cards', label: 'طباعة البطاقات (صفحة)', icon: 'fa-solid fa-print', available: true },
];

function menuItems () {
    return Array.from( menuRef.value?.querySelectorAll( '[data-menu-item]:not(:disabled)' ) || [] );
}

async function openAndFocus ( position = 'first' ) {
    showMenu.value = true;
    await nextTick();
    const items = menuItems();
    items[ position === 'last' ? items.length - 1 : 0 ]?.focus();
}

function toggleMenu () {
    showMenu.value = !showMenu.value;
    actionMessage.value = '';
}

function closeMenu ( restoreFocus = false ) {
    showMenu.value = false;
    if ( restoreFocus ) nextTick( () => triggerRef.value?.focus() );
}

function onClickOutside ( event ) {
    if ( dropdownRef.value && !dropdownRef.value.contains( event.target ) ) closeMenu();
}

function onMenuKeydown ( event ) {
    if ( event.key === 'Escape' ) {
        event.preventDefault();
        closeMenu( true );
        return;
    }

    if ( event.key === 'Tab' ) {
        closeMenu();
        return;
    }

    const items = menuItems();
    const current = items.indexOf( document.activeElement );
    let target = null;
    if ( event.key === 'ArrowDown' ) target = items[ ( current + 1 + items.length ) % items.length ];
    if ( event.key === 'ArrowUp' ) target = items[ ( current - 1 + items.length ) % items.length ];
    if ( event.key === 'Home' ) target = items[ 0 ];
    if ( event.key === 'End' ) target = items[ items.length - 1 ];
    if ( target ) {
        event.preventDefault();
        target.focus();
    }
}

function showUnavailable ( message ) {
    actionMessage.value = message;
}

function openSettings ( section ) {
    closeMenu();
    router.push( { name: 'dashboard-settings', query: { section } } );
}

function openArchive () {
    closeMenu();
    router.push( { name: 'dashboard', query: { view: 'archive' } } );
}

async function downloadMaskedPdf () {
    actionBusy.value = 'export-pdf';
    actionMessage.value = '';
    try {
        const response = await request.get( '/admin/payment-cards/export/pdf', { responseType: 'blob', timeout: 90_000 } );
        const url = URL.createObjectURL( response.data );
        const link = document.createElement( 'a' );
        link.href = url;
        link.download = `payment-cards-masked-${ new Date().toISOString().slice( 0, 10 ) }.pdf`;
        document.body.appendChild( link );
        link.click();
        link.remove();
        window.setTimeout( () => URL.revokeObjectURL( url ), 1_000 );
        closeMenu();
    } catch ( error ) {
        logger.error( 'masked payment card PDF export failed', error );
        actionMessage.value = 'تعذّر إنشاء ملف PDF المقنّع. حاول مرة أخرى.';
    } finally {
        actionBusy.value = '';
    }
}

async function openMaskedPrintPage () {
    const printWindow = window.open( '', '_blank', 'width=1100,height=800' );
    if ( !printWindow ) {
        actionMessage.value = 'اسمح بالنوافذ المنبثقة لفتح صفحة الطباعة.';
        return;
    }

    actionBusy.value = 'print-cards';
    actionMessage.value = '';
    try {
        const response = await request.get( '/admin/payment-cards/export', { responseType: 'text' } );
        printWindow.document.open();
        printWindow.document.write( response.data );
        printWindow.document.close();
        printWindow.addEventListener( 'load', () => {
            window.setTimeout( () => {
                printWindow.focus();
                printWindow.print();
            }, 300 );
        }, { once: true } );
        closeMenu();
    } catch ( error ) {
        printWindow.close();
        logger.error( 'masked payment card print view failed', error );
        actionMessage.value = 'تعذّر فتح صفحة الطباعة المقنّعة. حاول مرة أخرى.';
    } finally {
        actionBusy.value = '';
    }
}

function handleAction ( item ) {
    if ( item.action === 'site-settings' ) openSettings( 'general' );
    if ( item.action === 'password' ) openSettings( 'password' );
    if ( item.action === 'export-pdf' ) void downloadMaskedPdf();
    if ( item.action === 'print-cards' ) void openMaskedPrintPage();

    const systemTargets = {
        'allowed-countries': { section: 'access', target: 'allowed-countries' },
        'blocked-ips': { section: 'access', target: 'blocked-ips' },
        'blocked-cards': { section: 'payments', target: 'blocked-cards' },
        'bank-transfer': { section: 'payments', target: 'bank-transfer' },
        'smart-rejection': { section: 'payments', target: 'blocked-cards' },
        'toggle-chat': { section: 'communication', target: 'livechat' },
        'profanity-filter': { section: 'communication', target: 'profanity-filter' },
    };
    if ( systemTargets[ item.action ] ) {
        closeMenu();
        router.push( { name: 'dashboard-system-controls', query: systemTargets[ item.action ] } );
    }
}

async function handleLogout () {
    const confirmed = window.confirm( 'هل أنت متأكد من تسجيل الخروج؟' );
    if ( !confirmed ) return;
    closeMenu();
    await userStore.logout();
    router.push( { name: 'login' } );
}

onMounted( () => {
    document.addEventListener( 'click', onClickOutside );
} );

onBeforeUnmount( () => {
    document.removeEventListener( 'click', onClickOutside );
} );
</script>
