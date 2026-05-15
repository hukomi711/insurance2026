<template>
  <Teleport to="body">
    <Transition name="admin-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-stretch justify-center p-0 sm:items-center sm:p-4"
        :dir="dir"
        @click.self="$emit('close')"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0"
          :class="heavyBackdrop ? 'admin-modal-overlay--heavy' : 'admin-modal-overlay'"
          @click="$emit('close')"
        />

        <!-- Panel -->
        <div
          class="admin-modal-panel relative transform transition-all duration-200"
          :class="modalSizeClasses"
        >
          <!-- Header -->
          <div class="admin-modal-header">
            <div class="admin-modal-heading">
              <div
                v-if="icon"
                class="modal-icon-bg flex h-9 w-9 items-center justify-center rounded-lg"
              >
                <i :class="icon" class="h-5 w-5" :style="{ color: accentColor }" />
              </div>
              <div class="min-w-0">
                <h3 class="text-lg font-semibold tracking-tight" style="color: var(--admin-text, #fff)">
                  <span v-if="emoji" class="mr-1">{{ emoji }}</span>{{ title }}
                </h3>
                <p v-if="subtitle" class="text-xs" style="color: var(--admin-text-dim, #8b95a5)">{{ subtitle }}</p>
              </div>
            </div>
            <div class="admin-modal-header-actions">
              <div v-if="$slots['header-right']" class="admin-modal-header-slot">
                <slot name="header-right" />
              </div>
              <button
                class="admin-modal-close"
                @click="$emit('close')"
              >
                <i class="fa-solid fa-xmark h-5 w-5" />
              </button>
            </div>
          </div>

          <!-- Body -->
          <div
            class="admin-modal-body space-y-4"
          >
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="admin-modal-footer"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
  /** Controls visibility */
  open: { type: Boolean, default: false },
  /** Modal heading */
  title: { type: String, required: true },
  /** Optional subtitle under heading */
  subtitle: { type: String, default: '' },
  /** Responsive modal size token. */
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl', 'full'].includes(v),
  },
  /** Accent color (CSS value). Used for icon tint. */
  accent: { type: String, default: '#34d399' },
  /** FontAwesome icon class (e.g. 'fa-solid fa-credit-card') */
  icon: { type: String, default: '' },
  /** Emoji prefix for title (e.g. '🚗') */
  emoji: { type: String, default: '' },
  /** Text direction */
  dir: { type: String, default: 'rtl' },
  /** Theme variant */
  theme: {
    type: String,
    default: 'dark',
    validator: (v) => ['dark', 'light'].includes(v),
  },
  /** Whether backdrop is heavy (dark + blur) or standard */
  heavyBackdrop: { type: Boolean, default: false },
});

defineEmits(['close']);

const accentColor = computed(() => props.accent);
let previousBodyOverflow = '';

const modalSizeClasses = computed(() => {
  const sizes = {
    sm: 'w-[96vw] max-w-md',
    md: 'w-[96vw] max-w-2xl',
    lg: 'w-[96vw] max-w-4xl',
    xl: 'w-[96vw] max-w-6xl',
    full: 'w-screen sm:w-[96vw] sm:max-w-[90rem]',
  };

  return sizes[props.size] || sizes.md;
});

const lockBodyScroll = () => {
  if (typeof document === 'undefined') return;
  previousBodyOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';
};

const unlockBodyScroll = () => {
  if (typeof document === 'undefined') return;
  document.body.style.overflow = previousBodyOverflow;
};

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) lockBodyScroll();
    else unlockBodyScroll();
  },
  { immediate: true }
);

onBeforeUnmount(unlockBodyScroll);
</script>

<style scoped>
@reference "../../../../css/app.css";

.admin-modal-enter-active,
.admin-modal-leave-active {
  transition: opacity 0.2s ease;
}
.admin-modal-enter-from,
.admin-modal-leave-to {
  opacity: 0;
}

/* Icon background uses accent color */
.modal-icon-bg {
  background-color: color-mix(in srgb, v-bind(accentColor) 20%, transparent);
}
</style>
