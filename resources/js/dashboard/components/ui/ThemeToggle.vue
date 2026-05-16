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
    <i
      class="fa-solid text-base"
      :class="isDark ? 'fa-sun' : 'fa-moon'"
      aria-hidden="true"
    />
  </button>
</template>

<script setup>
import { useTheme } from '../../composables/useTheme';

const { isDark, toggle } = useTheme();
</script>
