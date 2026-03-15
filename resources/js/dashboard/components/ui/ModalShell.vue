<template>
  <Teleport to="body">
    <Transition name="admin-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        :dir="dir"
        @click.self="$emit('close')"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 transition-opacity duration-200"
          :class="backdropClass"
          @click="$emit('close')"
        />

        <!-- Panel -->
        <div
          class="admin-dark relative w-full transform overflow-hidden rounded-2xl shadow-2xl transition-all duration-200"
          :class="panelClasses"
          :style="{ maxWidth }"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between border-b px-6 py-4"
            :class="headerClasses"
          >
            <div class="flex items-center gap-3">
              <div
                v-if="icon"
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="iconBgClass"
              >
                <i :class="icon" class="h-5 w-5" :style="{ color: accentColor }" />
              </div>
              <div>
                <h3 class="text-lg font-semibold tracking-tight text-white">
                  <span v-if="emoji" class="mr-1">{{ emoji }}</span>{{ title }}
                </h3>
                <p v-if="subtitle" class="text-xs" :class="subtitleClass">{{ subtitle }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <slot v-if="$slots['header-right']" name="header-right" />
              <button
                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-all hover:bg-gray-700/50 hover:text-white"
                @click="$emit('close')"
              >
                <i class="fa-solid fa-xmark h-5 w-5" />
              </button>
            </div>
          </div>

          <!-- Body -->
          <div
            class="overflow-y-auto"
            :class="bodyClass"
            :style="{ maxHeight: bodyMaxHeight }"
          >
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="border-t px-6 py-4"
            :class="footerClasses"
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

const isDark = computed(() => props.theme === 'dark');

const backdropClass = computed(() =>
  props.heavyBackdrop
    ? 'bg-black/90 backdrop-blur-sm'
    : 'bg-black/50'
);

const panelClasses = computed(() =>
  isDark.value
    ? 'border border-gray-700 bg-gray-900'
    : 'bg-white'
);

const headerClasses = computed(() =>
  isDark.value
    ? 'border-gray-700 bg-gray-800'
    : 'border-gray-200 bg-gray-50'
);

const subtitleClass = computed(() =>
  isDark.value ? 'text-gray-500' : 'text-gray-400'
);

const iconBgClass = computed(() => {
  // Use accent color at 20% opacity
  return '';
});

const bodyClass = computed(() =>
  isDark.value ? 'p-6 space-y-4' : 'p-6 space-y-6'
);

const footerClasses = computed(() =>
  isDark.value
    ? 'border-gray-700 bg-gray-800'
    : 'border-gray-200 bg-gray-50'
);
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
[class*="items-center justify-center rounded-lg"]:first-child {
  background-color: color-mix(in srgb, v-bind(accentColor) 20%, transparent);
}
</style>
