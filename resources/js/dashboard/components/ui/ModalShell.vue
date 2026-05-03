<template>
  <Teleport to="body">
    <Transition name="admin-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4"
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
          class="admin-modal-panel relative w-full transform transition-all duration-200"
          :style="{ maxWidth: panelMaxWidth }"
        >
          <!-- Header -->
          <div class="admin-modal-header">
            <div class="flex items-center gap-3">
              <div
                v-if="icon"
                class="modal-icon-bg flex h-9 w-9 items-center justify-center rounded-lg"
              >
                <i :class="icon" class="h-5 w-5" :style="{ color: accentColor }" />
              </div>
              <div>
                <h3 class="text-lg font-semibold tracking-tight" style="color: var(--admin-text, #fff)">
                  <span v-if="emoji" class="mr-1">{{ emoji }}</span>{{ title }}
                </h3>
                <p v-if="subtitle" class="text-xs" style="color: var(--admin-text-dim, #8b95a5)">{{ subtitle }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <slot v-if="$slots['header-right']" name="header-right" />
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
            :style="{ maxHeight: bodyMaxHeight }"
            style="overflow-y: auto"
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
import { computed } from 'vue';

const props = defineProps({
  /** Controls visibility */
  open: { type: Boolean, default: false },
  /** Modal heading */
  title: { type: String, required: true },
  /** Optional subtitle under heading */
  subtitle: { type: String, default: '' },
  /** CSS max-width for the panel (e.g. '40rem', '90vw'). */
  maxWidth: { type: String, default: '42rem' },
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
  /** Max height for the scrollable body */
  bodyMaxHeight: { type: String, default: '65vh' },
  /** Whether backdrop is heavy (dark + blur) or standard */
  heavyBackdrop: { type: Boolean, default: false },
});

defineEmits(['close']);

const accentColor = computed(() => props.accent);
/** Cap panel width to viewport (handles 100vw edge case on phones) */
const panelMaxWidth = computed(() => `min(${props.maxWidth}, calc(100vw - 1rem))`);
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
