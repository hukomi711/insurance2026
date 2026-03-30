<template>
    <header class="w-full flex flex-col bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-40 safe-area-top"
        :class="{ 'shadow-sm': scrolled }">
        <div class="box h-16 flex items-center gap-4 z-50">
            <!-- Mobile Menu Button -->
            <button class="p-2 rounded-lg min-w-[44px] min-h-[44px] flex items-center justify-center bg-slate-100 md:hidden active:bg-slate-200 transition-colors"
                aria-label="فتح القائمة"
                @click="toggleMobileMenu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" width="24" height="24"
                    class="w-6 h-6">
                    <g clip-path="url(#clip0_menu)">
                        <path d="M4 6L20 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </g>
                    <defs>
                        <clipPath id="clip0_menu">
                            <rect width="24" height="24" fill="white" />
                        </clipPath>
                    </defs>
                </svg>
            </button>

            <!-- Logo & Brand Section -->
            <div class="grow ms-0 md:ms-4">
                <div class="flex items-center gap-4">
                    <!-- Logo -->
                    <router-link to="/" class="flex items-center gap-2">
                        <!-- SVG Logo - Show on mobile -->
                        <svg viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"
                            class="md:hidden w-8 h-8 text-primary">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M5.67889 9.56532H4.63534C4.46705 9.56595 4.30545 9.49853 4.18623 9.37795C4.06701 9.25737 4 9.09357 4 8.92273V3.73527C4 2.76424 4.77543 1.97707 5.73197 1.97707H10.7046C11.2572 1.97738 11.7985 1.81858 12.2657 1.5191L14.3692 0.172116C14.7281 -0.057372 15.1849 -0.057372 15.5439 0.172116L17.7715 1.59833C18.157 1.84521 18.6035 1.97624 19.0593 1.97628H24.32C25.1985 1.97628 25.9107 2.69924 25.9107 3.59107V4.95865C25.9107 5.07829 25.9154 9.57958 25.9154 9.57958C25.9555 9.98874 25.7669 10.3863 25.4268 10.6096L15.5228 17.9538C15.3897 18.0488 15.2156 18.0606 15.0713 17.9845C14.927 17.9083 14.8365 17.7568 14.8367 17.5917V15.9048C14.8368 15.5009 15.0275 15.1215 15.3495 14.8843L23.0915 9.10021C23.3945 8.88284 23.5703 8.52594 23.5598 8.1494L23.5465 5.3667C23.537 5.00717 23.3781 4.66852 23.109 4.4345C22.84 4.20049 22.486 4.09298 22.1345 4.13857L18.8759 4.16314C18.2154 4.18963 17.5615 4.02208 16.9925 3.6806L15.3183 2.61253C15.0978 2.46288 14.8096 2.4654 14.5916 2.61886L13.0782 3.55303C12.4254 3.95097 11.6792 4.16334 10.9177 4.16789L7.63175 4.1877C6.8821 4.1925 6.27738 4.8117 6.27911 5.57271L6.28848 8.94491C6.28889 9.10931 6.22485 9.26711 6.11049 9.38351C5.99612 9.4999 5.84084 9.56532 5.67889 9.56532ZM24.9793 13.0546L15.8715 19.8925C15.3289 20.3037 14.5845 20.3037 14.0419 19.8925L4.94031 13.0546C4.76141 12.9199 4.5228 12.8996 4.32429 13.0022C4.12579 13.1048 4.0016 13.3126 4.00369 13.5387V21.0335C4.00263 21.9452 4.43588 22.8012 5.16666 23.3313L13.8827 29.6518C14.5262 30.1161 15.3887 30.1161 16.0322 29.6518L24.7483 23.3313C25.4769 22.7993 25.9095 21.9445 25.9113 21.0335V13.5324C25.9114 13.3083 25.7872 13.1033 25.5902 13.0022C25.3932 12.9012 25.1569 12.9215 24.9793 13.0546ZM23.5214 20.2529C23.5471 20.8549 23.278 21.4309 22.8025 21.7916L16.1314 26.972C15.4368 27.5022 14.4805 27.5022 13.786 26.972L6.93847 21.7053C6.86574 21.6598 6.79977 21.6041 6.74256 21.5397C6.49751 21.2822 6.33951 20.9523 6.29142 20.5976C6.27128 20.5084 6.26029 20.4173 6.25864 20.3258L6.27815 18.1699C6.2753 17.9895 6.37326 17.823 6.53106 17.7401C6.68886 17.6571 6.87931 17.672 7.02277 17.7784L14.0724 22.912C14.5994 23.2966 15.3094 23.2966 15.8364 22.912L22.8407 17.8046C22.9712 17.7094 23.1431 17.6961 23.2863 17.7702C23.4294 17.8443 23.5198 17.9933 23.5206 18.1564L23.5214 20.2529Z"
                                fill="currentColor" />
                        </svg>
                        <!-- Brand Name - Hidden on mobile, show only on desktop -->
                        <span
                            class="hidden md:inline text-lg md:text-xl font-bold text-primary font-heading">تأمينكم</span>
                    </router-link>

                    <!-- Divider -->
                    <div class="shrink-0 w-[1px] hidden md:block h-3 bg-slate-300"></div>

                    <!-- Category Label - Desktop only -->
                    <span class="text-sm text-slate-500 font-normal hidden md:block">مقارنة أسعار التأمين</span>
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
                <!-- Language Button -->
                <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 active:bg-slate-200 transition-colors cursor-pointer"
                    @click="toggleLocale">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <span>EN</span>
                </button>
            </div>
        </div>

        <!-- Mobile Menu — Teleported to body to escape header stacking context -->
        <Teleport to="body">
            <!-- Overlay -->
            <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="mobileMenuOpen" class="fixed inset-0 bg-black/50 z-[9998] md:hidden" @click="toggleMobileMenu"></div>
            </Transition>

            <!-- Sidebar -->
            <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-x-full"
                enter-to-class="translate-x-0" leave-active-class="transition-transform duration-300 ease-in"
                leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                <div v-if="mobileMenuOpen" ref="mobileMenuRef"
                    class="fixed top-0 right-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl z-[9999] md:hidden overflow-y-auto safe-area-top"
                dir="rtl" role="dialog" aria-modal="true" aria-label="القائمة الرئيسية"
                @touchstart.passive="onTouchStart" @touchmove.passive="onTouchMove" @touchend.passive="onTouchEnd">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-bold text-slate-900 font-heading">القائمة</h2>
                        <button class="p-2 rounded-sm hover:bg-slate-100 transition-colors" aria-label="إغلاق القائمة" @click="toggleMobileMenu">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Menu Items -->
                    <nav class="space-y-1 p-2">
                        <router-link v-for="link in navLinks" :key="link.to" :to="link.to"
                            class="block py-3.5 px-4 min-h-[48px] rounded-lg text-base text-slate-700 hover:text-blue-600 hover:bg-blue-50 active:bg-blue-100 transition-colors font-medium"
                            @click="mobileMenuOpen = false">
                            <div class="flex items-center gap-3">
                                <component :is="link.icon" class="text-xl text-slate-400" />
                                <span>{{ link.label }}</span>
                            </div>
                        </router-link>

                        <!-- Divider -->
                        <div class="my-4 border-t border-slate-200"></div>

                        <router-link to="/contact" class="block py-3.5 px-4 min-h-[48px] rounded-lg text-base text-slate-700 hover:text-blue-600 hover:bg-blue-50 active:bg-blue-100 transition-colors"
                            @click="mobileMenuOpen = false">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-envelope text-xl text-slate-400"></i>
                                <span>تواصل معنا</span>
                            </div>
                        </router-link>

                        <router-link to="/faq" class="block py-3.5 px-4 min-h-[48px] rounded-lg text-base text-slate-700 hover:text-blue-600 hover:bg-blue-50 active:bg-blue-100 transition-colors"
                            @click="mobileMenuOpen = false">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-circle-question text-xl text-slate-400"></i>
                                <span>الأسئلة الشائعة</span>
                            </div>
                        </router-link>
                    </nav>
                </div>
            </div>
        </Transition>
        </Teleport>
    </header>
</template>

<script setup>
import { ref, h, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { switchLocale } from '@/i18n';

const mobileMenuOpen = ref( false );
const mobileMenuRef = ref( null );
const scrolled = ref( false );

// ── Sticky header scroll detection ──
function handleScroll () {
    scrolled.value = window.scrollY > 10;
}

onMounted( () => {
    window.addEventListener( 'scroll', handleScroll, { passive: true } );
    handleScroll();
} );

// ── Swipe-to-close for mobile sidebar ──
let touchStartX = 0;
let _touchStartY = 0;
let touchDeltaX = 0;

function onTouchStart ( e ) {
    const touch = e.touches[ 0 ];
    touchStartX = touch.clientX;
    _touchStartY = touch.clientY;
    touchDeltaX = 0;
}

function onTouchMove ( e ) {
    const touch = e.touches[ 0 ];
    touchDeltaX = touch.clientX - touchStartX;
}

function onTouchEnd () {
    // Swipe right (RTL: swipe toward the right edge to close)
    if ( touchDeltaX > 80 ) {
        mobileMenuOpen.value = false;
    }
    touchDeltaX = 0;
}

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

// Lock body scroll when mobile menu is open + trap focus
watch( mobileMenuOpen, async ( isOpen ) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
    if ( isOpen ) {
        await nextTick();
        trapFocus();
    }
} );

/** Trap focus inside the mobile menu sidebar */
function trapFocus () {
    const menu = mobileMenuRef.value;
    if ( !menu ) return;
    const focusable = menu.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    if ( !focusable.length ) return;
    const first = focusable[ 0 ];
    const last = focusable[ focusable.length - 1 ];
    first.focus();
    menu._handleTab = ( e ) => {
        if ( e.key === 'Escape' ) { mobileMenuOpen.value = false; return; }
        if ( e.key !== 'Tab' ) return;
        if ( e.shiftKey ) {
            if ( document.activeElement === first ) { e.preventDefault(); last.focus(); }
        } else {
            if ( document.activeElement === last ) { e.preventDefault(); first.focus(); }
        }
    };
    menu.addEventListener( 'keydown', menu._handleTab );
}

onUnmounted( () => {
    document.body.style.overflow = '';
    window.removeEventListener( 'scroll', handleScroll );
    if ( mobileMenuRef.value?._handleTab ) {
        mobileMenuRef.value.removeEventListener( 'keydown', mobileMenuRef.value._handleTab );
    }
} );

const toggleLocale = () => {
    switchLocale();
};

/* Simple inline icon components for mobile nav */
const IconHome = { render: () => h('i', { class: 'fa-solid fa-house' }) };
const IconCar = { render: () => h('i', { class: 'fa-solid fa-car' }) };
const IconAbout = { render: () => h('i', { class: 'fa-solid fa-circle-info' }) };

const navLinks = [
    { to: '/', label: 'الرئيسية', icon: IconHome },
    { to: '/motorapp', label: 'تأمين المركبات', icon: IconCar },
    { to: '/about', label: 'من نحن', icon: IconAbout },
];
</script>
