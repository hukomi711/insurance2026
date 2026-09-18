<template>
  <Teleport to="body">
    <Transition name="admin-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-stretch justify-center p-0 sm:items-center sm:p-4"
        :dir="dir"
        data-admin-theme="dark"
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
          class="admin-modal-panel relative flex h-[100dvh] w-full flex-col overflow-hidden rounded-none transition-all duration-200 sm:h-auto sm:max-h-[90dvh] sm:w-[96vw] sm:rounded-2xl"
          :class="modalSizeClasses"
        >
          <!-- Header -->
          <div
            class="admin-modal-header shrink-0 items-start gap-3"
            :class="{ 'admin-modal-header--with-bottom': $slots['header-bottom'] }"
          >
            <div class="admin-modal-heading items-start gap-3">
              <div
                v-if="icon"
                class="modal-icon-bg flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              >
                <i :class="icon" class="h-5 w-5" :style="{ color: accentColor }" />
              </div>
              <div class="min-w-0">
                <h3 class="truncate text-lg font-semibold tracking-tight sm:text-xl" style="color: var(--admin-text, #fff)">
                  <span v-if="emoji" class="me-1">{{ emoji }}</span>{{ title }}
                </h3>
                <p v-if="subtitle" class="truncate text-xs sm:text-sm" style="color: var(--admin-text-dim, #8b95a5)">{{ subtitle }}</p>
              </div>
            </div>
            <div class="admin-modal-header-actions shrink-0">
              <div v-if="$slots['header-right']" class="admin-modal-header-slot">
                <slot name="header-right" />
              </div>
              <button
                type="button"
                class="admin-modal-close shrink-0"
                aria-label="إغلاق النافذة"
                title="إغلاق"
                @click="$emit('close')"
              >
                <i class="fa-solid fa-xmark h-5 w-5" />
              </button>
            </div>
          </div>

          <div v-if="$slots['header-bottom']" class="admin-modal-header-bottom">
            <slot name="header-bottom" />
          </div>

          <!-- Body -->
          <div
            class="admin-modal-body flex-1 min-h-0 overscroll-contain space-y-4 px-4 py-4 sm:space-y-5 sm:px-5 sm:py-5"
          >
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="admin-modal-footer shrink-0 px-4 py-3 sm:px-5 sm:py-4"
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

.admin-modal-header--with-bottom {
  border-bottom: 0;
}

.admin-modal-header--with-bottom .admin-modal-close {
  width: 2.75rem;
  height: 2.75rem;
}

.admin-modal-header-bottom {
  min-width: 0;
  flex-shrink: 0;
  overflow: hidden;
  background: var(--admin-surface, #1a1f2e);
}

@media (max-width: 640px) {
  .admin-modal-header--with-bottom {
    flex-wrap: nowrap;
  }

  .admin-modal-header--with-bottom .admin-modal-heading {
    flex: 1 1 auto;
  }

  .admin-modal-header--with-bottom .admin-modal-header-actions {
    flex: 0 0 auto;
  }
}
</style>
