<template>
    <div ref="dropdownRef" class="relative">
        <button
            type="button"
            class="admin-touch flex h-10 items-center gap-2 rounded-xl border-r border-gray-200 pr-3 transition-colors hover:bg-gray-100/70"
            :aria-label="showMenu ? 'إغلاق قائمة الحساب' : 'فتح قائمة الحساب'"
            :aria-expanded="showMenu"
            @click="toggleMenu"
        >
            <div
                class="h-8 w-8 shrink-0 rounded-full bg-[var(--color-primary-dark)] flex items-center justify-center text-sm font-bold text-white"
            >
                {{ userStore.initials }}
            </div>

            <div class="hidden min-w-0 lg:block">
                <p class="max-w-[14rem] truncate text-sm font-medium leading-none text-gray-700">
                    {{ userStore.name }}
                </p>
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
                class="absolute right-0 top-full z-50 mt-2 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white p-2 shadow-xl"
                dir="rtl"
            >
                <div class="rounded-xl bg-gray-50 px-3 py-3">
                    <p class="truncate text-sm font-semibold text-gray-800">
                        {{ userStore.name }}
                    </p>
                    <p class="truncate text-xs text-gray-500" dir="ltr">
                        {{ userStore.email }}
                    </p>
                </div>

                <div class="my-2 h-px bg-gray-100"></div>

                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-gray-50"
                    @click="openProfile"
                >
                    <i class="fa-solid fa-user w-4 text-center text-[13px] text-gray-500" aria-hidden="true"></i>
                    <span>الملف الشخصي</span>
                </button>

                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm transition-colors hover:bg-gray-50"
                    @click="openSettings"
                >
                    <i class="fa-solid fa-gear w-4 text-center text-[13px] text-gray-500" aria-hidden="true"></i>
                    <span>الإعدادات</span>
                </button>

                <div class="my-2 h-px bg-gray-100"></div>

                <button
                    type="button"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm text-red-600 transition-colors hover:bg-red-50"
                    @click="handleLogout"
                >
                    <i class="fa-solid fa-right-from-bracket w-4 text-center text-[13px]" aria-hidden="true"></i>
                    <span>تسجيل الخروج</span>
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/store/modules/user';

const router = useRouter();
const userStore = useUserStore();
const dropdownRef = ref(null);
const showMenu = ref(false);

function toggleMenu() {
    showMenu.value = !showMenu.value;
}

function closeMenu() {
    showMenu.value = false;
}

function onClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeMenu();
    }
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        closeMenu();
    }
}

function openProfile() {
    closeMenu();
    router.push({ name: 'dashboard-settings', query: { section: 'account' } });
}

function openSettings() {
    closeMenu();
    router.push({ name: 'dashboard-settings', query: { section: 'general' } });
}

async function handleLogout() {
    closeMenu();
    await userStore.logout();
    router.push({ name: 'login' });
}

onMounted(() => {
    document.addEventListener('click', onClickOutside);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});
</script>
