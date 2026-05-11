<template>
  <transition name="wa-pop">
    <a
      v-if="visible"
      :href="href"
      target="_blank"
      rel="noopener noreferrer"
      class="fixed bottom-4 left-4 lg:bottom-6 lg:left-6 z-30 group flex items-center justify-center w-14 h-14 lg:w-[60px] lg:h-[60px] rounded-full shadow-lg hover:shadow-xl bg-[#25D366] hover:bg-[#1ebe5b] text-white transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-[#25D366]/40"
      :aria-label="ariaLabel"
      @click="onClick"
    >
      <!-- Pulse ring -->
      <span class="absolute inset-0 rounded-full bg-[#25D366] opacity-60 animate-wa-pulse pointer-events-none" aria-hidden="true"></span>

      <!-- WhatsApp glyph (inline SVG — no external CDN, CSP-safe) -->
      <svg
        class="relative w-7 h-7 lg:w-8 lg:h-8"
        viewBox="0 0 32 32"
        fill="currentColor"
        aria-hidden="true"
      >
        <path d="M19.11 17.21c-.27-.13-1.59-.78-1.83-.87-.25-.09-.43-.13-.6.13-.18.27-.7.87-.85 1.05-.16.18-.31.2-.58.07-.27-.13-1.13-.42-2.15-1.33-.8-.71-1.34-1.59-1.5-1.86-.16-.27-.02-.42.12-.55.12-.12.27-.31.4-.47.13-.16.18-.27.27-.45.09-.18.04-.34-.02-.47-.07-.13-.6-1.45-.82-1.98-.22-.52-.45-.45-.6-.45h-.51c-.18 0-.47.07-.71.34-.25.27-.93.91-.93 2.22 0 1.31.95 2.58 1.09 2.76.13.18 1.87 2.85 4.53 4 .63.27 1.13.43 1.51.55.63.2 1.21.17 1.67.1.51-.08 1.59-.65 1.81-1.27.22-.62.22-1.16.16-1.27-.07-.11-.25-.18-.52-.31zM16.02 6.4h-.01c-5.31 0-9.62 4.31-9.62 9.61 0 1.91.55 3.78 1.6 5.4l-1.04 3.78 3.88-1.02a9.6 9.6 0 0 0 5.18 1.5h.01c5.3 0 9.61-4.31 9.61-9.61 0-2.57-1-4.98-2.82-6.79a9.55 9.55 0 0 0-6.79-2.87zm0 17.59h-.01a8 8 0 0 1-4.07-1.12l-.29-.17-3.01.79.8-2.93-.19-.3a7.99 7.99 0 0 1-1.23-4.26c0-4.41 3.59-8 8.01-8a7.96 7.96 0 0 1 5.66 2.35 7.97 7.97 0 0 1 2.35 5.66c0 4.4-3.59 7.98-8.02 7.98z" />
      </svg>

      <!-- Tooltip (desktop only) -->
      <span class="pointer-events-none absolute left-full ml-3 hidden lg:inline-block whitespace-nowrap rounded-md bg-gray-900 text-white text-xs font-medium px-3 py-1.5 opacity-0 translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200">
        تواصل عبر واتساب
      </span>
    </a>
  </transition>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useSiteConfig } from '@/composables/useSiteConfig';

defineProps({
    ariaLabel: { type: String, default: 'تواصل عبر واتساب' },
});

const { state, whatsappEnabled, whatsappLink, load } = useSiteConfig();

onMounted(() => {
    // Fire-and-forget; visibility is reactive to state.isLoaded.
    load();
});

const visible = computed(() => state.isLoaded && whatsappEnabled.value);
const href = computed(() => whatsappLink.value || '#');

function onClick(event) {
    if (!whatsappLink.value) {
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
