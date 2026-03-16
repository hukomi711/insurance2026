<template>
  <div class="reject-picker">
    <select
      id="reject-reason"
      v-model="selected"
      name="reject-reason"
      class="reject-picker__select"
      :disabled="disabled"
    >
      <option value="" disabled>اختر سبب الرفض...</option>
      <option v-for="opt in options" :key="opt.key" :value="opt.key">
        {{ opt.label }}
      </option>
    </select>
    <AdminButton
      variant="reject"
      :size="size"
      :disabled="disabled || !selected"
      class="reject-picker__btn"
      @click="$emit('reject', selected)"
    >
      رفض
    </AdminButton>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { getReasonOptions } from '@/constants/rejectionReasons';
import { AdminButton } from './ui';

const props = defineProps({
  action: { type: String, required: true },
  disabled: { type: Boolean, default: false },
  size: { type: String, default: 'sm' },
});

defineEmits(['reject']);

const { t } = useI18n();
const selected = ref('');

const options = computed(() => getReasonOptions(props.action, t));

// Reset selection when action changes
watch(() => props.action, () => { selected.value = ''; });
</script>

<style scoped>
.reject-picker {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.reject-picker__select {
  width: 100%;
  padding: 0.375rem 0.5rem;
  font-size: 0.75rem;
  color: #e2e8f0;
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 0.5rem;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s;
  direction: rtl;
}

.reject-picker__select:focus {
  border-color: rgba(248, 113, 113, 0.6);
  box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.15);
}

.reject-picker__select:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.reject-picker__select option {
  background: #1e293b;
  color: #e2e8f0;
}

.reject-picker__btn {
  width: 100%;
}
</style>
