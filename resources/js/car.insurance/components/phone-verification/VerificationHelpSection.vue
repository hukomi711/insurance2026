<template>
  <div class="mt-4 bg-white/5 backdrop-blur rounded-xl p-4 border border-white/10">
    <h3 class="text-sm font-semibold text-white/80 mb-2 flex items-center gap-2">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
          clip-rule="evenodd"
        />
      </svg>
      {{ t('verification.phone.helpTitle') }}
    </h3>
    <ul class="text-xs text-white/60 space-y-1">
      <li>• {{ t('verification.phone.helpCheckPhone') }}</li>
      <li>• {{ t('verification.phone.helpCheckConnection') }}</li>
      <li>• {{ t('verification.phone.helpWaitMinutes') }}</li>
    </ul>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
</script>
