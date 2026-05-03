<template>
  <div class="reject-picker">
    <!-- Collapsed: compact trigger -->
    <button
      v-if="!expanded"
      type="button"
      class="reject-picker__trigger"
      :disabled="disabled"
      @click="open"
    >
      <i class="fa-solid fa-xmark reject-picker__trigger-icon" aria-hidden="true"></i>
      <span>{{ triggerLabel || 'رفض' }}</span>
    </button>

    <!-- Expanded: reason selection panel -->
    <transition name="reject-picker-fade">
      <div v-if="expanded" class="reject-picker__panel" role="group" :aria-label="`اختيار سبب الرفض`">
        <label :for="`reject-reason-${action}`" class="reject-picker__hint">
          <i class="fa-solid fa-circle-info reject-picker__hint-icon" aria-hidden="true"></i>
          سبب الرفض
        </label>
        <select
          :id="`reject-reason-${action}`"
          v-model="selected"
          :name="`reject-reason-${action}`"
          class="reject-picker__select"
          :disabled="disabled"
          @keydown.escape="cancel"
        >
          <option value="" disabled>اختر سبباً...</option>
          <option v-for="opt in options" :key="opt.key" :value="opt.key">
            {{ opt.label }}
          </option>
        </select>
        <div class="reject-picker__row">
          <button
            type="button"
            class="reject-picker__cancel"
            :disabled="disabled"
            @click="cancel"
          >
            إلغاء
          </button>
          <button
            type="button"
            class="reject-picker__confirm"
            :disabled="disabled || !selected"
            @click="confirm"
          >
            <i class="fa-solid fa-check reject-picker__confirm-icon" aria-hidden="true"></i>
            تأكيد الرفض
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { getReasonOptions } from '@/constants/rejectionReasons';

const props = defineProps({
  action: { type: String, required: true },
  disabled: { type: Boolean, default: false },
  triggerLabel: { type: String, default: '' },
});

const emit = defineEmits(['reject']);

const { t } = useI18n();
const selected = ref('');
const expanded = ref(false);

const options = computed(() => getReasonOptions(props.action, t));

function open () {
  if (props.disabled) return;
  expanded.value = true;
}

function cancel () {
  expanded.value = false;
  selected.value = '';
}

function confirm () {
  if (!selected.value || props.disabled) return;
  emit('reject', selected.value);
  // Auto-collapse after action — buttons will fully unmount when status leaves "pending"
  expanded.value = false;
  selected.value = '';
}

// Reset whenever the action prop changes (component reused)
watch(() => props.action, () => {
  selected.value = '';
  expanded.value = false;
});
</script>

<style scoped>
.reject-picker {
  display: flex;
  flex-direction: column;
}

/* ── Collapsed trigger ─────────────────────────── */
.reject-picker__trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgb(252 165 165);
  background: rgba(127, 29, 29, 0.18);
  border: 1px solid rgba(248, 113, 113, 0.35);
  border-radius: 0.625rem;
  cursor: pointer;
  user-select: none;
  transition: all 0.18s ease;
}

.reject-picker__trigger:hover:not(:disabled) {
  color: #fff;
  background: rgba(220, 38, 38, 0.85);
  border-color: rgba(248, 113, 113, 0.6);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px -4px rgba(220, 38, 38, 0.5);
}

.reject-picker__trigger:active:not(:disabled) {
  transform: scale(0.97);
}

.reject-picker__trigger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.reject-picker__trigger-icon {
  font-size: 0.75rem;
}

/* ── Expanded panel ────────────────────────────── */
.reject-picker__panel {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.625rem;
  background: linear-gradient(180deg, rgba(127, 29, 29, 0.18), rgba(15, 23, 42, 0.6));
  border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 0.625rem;
}

.reject-picker__hint {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  color: rgb(252 165 165);
}

.reject-picker__hint-icon {
  font-size: 0.6875rem;
  opacity: 0.75;
}

.reject-picker__select {
  width: 100%;
  padding: 0.4rem 0.5rem;
  font-size: 0.75rem;
  color: #e2e8f0;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 0.5rem;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
  direction: rtl;
}

.reject-picker__select:focus {
  border-color: rgba(248, 113, 113, 0.7);
  box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.18);
}

.reject-picker__select:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.reject-picker__select option {
  background: #1e293b;
  color: #e2e8f0;
}

.reject-picker__row {
  display: grid;
  grid-template-columns: 1fr 1.6fr;
  gap: 0.4rem;
}

.reject-picker__cancel,
.reject-picker__confirm {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  padding: 0.4rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
  border: 1px solid transparent;
}

.reject-picker__cancel {
  color: #cbd5e1;
  background: rgba(51, 65, 85, 0.6);
  border-color: rgba(100, 116, 139, 0.3);
}

.reject-picker__cancel:hover:not(:disabled) {
  background: rgba(71, 85, 105, 0.8);
  color: #fff;
}

.reject-picker__confirm {
  color: #fff;
  background: rgb(220 38 38);
  border-color: rgba(248, 113, 113, 0.5);
  box-shadow: 0 2px 8px -2px rgba(220, 38, 38, 0.4);
}

.reject-picker__confirm:hover:not(:disabled) {
  background: rgb(185 28 28);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px -2px rgba(220, 38, 38, 0.5);
}

.reject-picker__confirm:active:not(:disabled),
.reject-picker__cancel:active:not(:disabled) {
  transform: scale(0.97);
}

.reject-picker__confirm:disabled,
.reject-picker__cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.reject-picker__confirm-icon {
  font-size: 0.6875rem;
}

/* ── Transition ─────────────────────────────────── */
.reject-picker-fade-enter-active,
.reject-picker-fade-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.reject-picker-fade-enter-from,
.reject-picker-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>

