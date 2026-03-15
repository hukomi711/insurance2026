<template>
  <button
    class="relative flex items-center justify-center w-9 h-9 rounded-lg transition-all duration-200"
    :class="isDark
      ? 'text-amber-400 hover:bg-gray-700/60'
      : 'text-gray-500 hover:bg-gray-100'"
    :title="isDark ? 'الوضع الفاتح' : 'الوضع الداكن'"
    :aria-label="isDark ? 'التبديل إلى الوضع الفاتح' : 'التبديل إلى الوضع الداكن'"
    @click="toggle"
  >
    <!-- Sun icon (shown in dark mode → click to go light) -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 rotate-[-30deg] scale-75"
      enter-to-class="opacity-100 rotate-0 scale-100"
      leave-active-class="transition duration-150 ease-in absolute"
      leave-from-class="opacity-100 rotate-0 scale-100"
      leave-to-class="opacity-0 rotate-[30deg] scale-75"
    >
      <i v-if="isDark" class="fa-solid fa-sun text-base" aria-hidden="true" />
    </Transition>

    <!-- Moon icon (shown in light mode → click to go dark) -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 rotate-[30deg] scale-75"
      enter-to-class="opacity-100 rotate-0 scale-100"
      leave-active-class="transition duration-150 ease-in absolute"
      leave-from-class="opacity-100 rotate-0 scale-100"
      leave-to-class="opacity-0 rotate-[-30deg] scale-75"
    >
      <i v-if="!isDark" class="fa-solid fa-moon text-base" aria-hidden="true" />
    </Transition>
  </button>
</template>

<script setup>
import { useTheme } from '../../composables/useTheme';

const { isDark, toggle } = useTheme();
</script>
