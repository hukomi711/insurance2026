<template>
  <transition name="wa-pop">
    <a
      v-if="visible"
      :href="href"
      rel="noopener noreferrer"
      class="fixed bottom-4 left-4 lg:bottom-6 lg:left-6 z-30 group flex items-center justify-center w-14 h-14 lg:w-[60px] lg:h-[60px] rounded-full shadow-lg hover:shadow-xl bg-[#25D366] hover:bg-[#1ebe5b] text-white transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-[#25D366]/40"
      :aria-label="ariaLabel"
      @click="onClick"
    >
      <!-- Pulse ring -->
      <span class="absolute inset-0 rounded-full bg-[#25D366] opacity-60 animate-wa-pulse pointer-events-none" aria-hidden="true"></span>

      <!-- Phone glyph (inline SVG — no external CDN, CSP-safe) -->
      <svg
        class="relative w-7 h-7 lg:w-8 lg:h-8"
        viewBox="0 0 24 24"
        fill="currentColor"
        aria-hidden="true"
      >
        <path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24 11.36 11.36 0 0 0 3.56.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.56 1 1 0 0 1-.24 1.02l-2.2 2.21z" />
      </svg>

      <!-- Tooltip (desktop only) -->
      <span class="pointer-events-none absolute left-full ml-3 hidden lg:inline-block whitespace-nowrap rounded-md bg-gray-900 text-white text-xs font-medium px-3 py-1.5 opacity-0 translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200">
        اتصل بنا
      </span>
    </a>
  </transition>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useSiteConfig } from '@/composables/useSiteConfig';

defineProps({
    ariaLabel: { type: String, default: 'اتصل بنا' },
});

const { state, load } = useSiteConfig();

onMounted(() => {
    // Fire-and-forget; visibility is reactive to state.isLoaded.
    load();
});

const telLink = computed(() => {
    const raw = state.whatsapp.number || '';
    const digits = raw.replace(/\D/g, '');
    if (!digits) return null;
    // Saudi local format: leading 0 → +966
    const intl = digits.startsWith('0') ? '966' + digits.slice(1) : digits;
    return 'tel:+' + intl;
});

const visible = computed(() => state.isLoaded && state.whatsapp.enabled && !!telLink.value);
const href = computed(() => telLink.value || '#');

function onClick(event) {
    if (!telLink.value) {
        event.preventDefault();
    }
}
</script>

<style scoped>
@keyframes wa-pulse {
  0%   { transform: scale(1);   opacity: 0.6; }
  70%  { transform: scale(1.6); opacity: 0;   }
  100% { transform: scale(1.6); opacity: 0;   }
}

.animate-wa-pulse {
  animation: wa-pulse 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Reduced motion: disable pulse */
@media (prefers-reduced-motion: reduce) {
  .animate-wa-pulse { animation: none; opacity: 0; }
}

.wa-pop-enter-active,
.wa-pop-leave-active {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s;
}
.wa-pop-enter-from,
.wa-pop-leave-to {
  transform: scale(0.5) translateY(20px);
  opacity: 0;
}
</style>
