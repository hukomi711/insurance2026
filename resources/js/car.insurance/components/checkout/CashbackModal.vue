<template>
  <teleport to="body">
    <transition name="cb-fade">
      <div v-if="visible" class="cb-overlay" dir="rtl" @click.self="$emit('close')">
        <div class="cb-modal">
          <!-- Close -->
          <button class="cb-close" @click="$emit('close')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Cashback Image -->
          <img :src="cashbackImage" alt="كاش باك 30%" class="cb-image" width="320" height="180" />

          <!-- Text -->
          <div class="cb-body">
            <h3 class="cb-title">🎉 عرض كاش باك 30%</h3>
            <p class="cb-desc">أكمل عملية الدفع الآن واحصل على كاش باك 30% عند استخدام بطاقتك الائتمانية!</p>

            <!-- Timer -->
            <div class="cb-timer-wrap">
              <span class="cb-timer-label">ينتهي العرض خلال</span>
              <div class="cb-timer">
                <div class="cb-timer__block">
                  <span class="cb-timer__num">{{ minutes }}</span>
                  <span class="cb-timer__unit">دقيقة</span>
                </div>
                <span class="cb-timer__sep">:</span>
                <div class="cb-timer__block">
                  <span class="cb-timer__num">{{ seconds }}</span>
                  <span class="cb-timer__unit">ثانية</span>
                </div>
              </div>
            </div>

            <button class="cb-cta" @click="$emit('close')">أكمل الدفع الآن</button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { CASHBACK_SUMMARY_IMAGE } from '@/constants/cashbackImage';

defineProps({
  visible: { type: Boolean, default: false },
});

defineEmits(['close']);

const cashbackImage = CASHBACK_SUMMARY_IMAGE;

const DURATION = 30 * 60; // 30 minutes in seconds
const remaining = ref(DURATION);
let interval = null;

const minutes = computed(() => String(Math.floor(remaining.value / 60)).padStart(2, '0'));
const seconds = computed(() => String(remaining.value % 60).padStart(2, '0'));

onMounted(() => {
  // Resume timer from sessionStorage if it was already started
  const saved = sessionStorage.getItem('cashbackTimerEnd');
  if (saved) {
    const endTime = parseInt(saved, 10);
    const left = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    remaining.value = left;
  } else {
    const endTime = Date.now() + DURATION * 1000;
    sessionStorage.setItem('cashbackTimerEnd', String(endTime));
    remaining.value = DURATION;
  }

  interval = setInterval(() => {
    if (remaining.value > 0) {
      remaining.value--;
    } else {
      clearInterval(interval);
    }
  }, 1000);
});

onUnmounted(() => {
  if (interval) clearInterval(interval);
});
</script>

<style scoped>
.cb-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.55);
  padding: 1rem;
}

.cb-modal {
  position: relative;
  width: 100%;
  max-width: 400px;
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.cb-close {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 2;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.85);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #555;
  transition: background 0.15s;
}

.cb-close:hover {
  background: #fff;
  color: #000;
}

.cb-image {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
}

.cb-body {
  padding: 20px 24px 24px;
  text-align: center;
}

.cb-title {
  font-size: 20px;
  font-weight: 800;
  color: #1a1a1a;
  margin: 0 0 8px;
}

.cb-desc {
  font-size: 14px;
  color: #555;
  line-height: 1.7;
  margin: 0 0 16px;
}

.cb-timer-wrap {
  margin-bottom: 20px;
}

.cb-timer-label {
  font-size: 13px;
  color: #888;
  display: block;
  margin-bottom: 8px;
}

.cb-timer {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.cb-timer__block {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 52px;
  padding: 8px 12px;
  background: #fef3c7;
  border-radius: 10px;
}

.cb-timer__num {
  font-size: 26px;
  font-weight: 800;
  color: #d97706;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.cb-timer__unit {
  font-size: 11px;
  color: #92400e;
  margin-top: 2px;
}

.cb-timer__sep {
  font-size: 22px;
  font-weight: 700;
  color: #d97706;
}

.cb-cta {
  width: 100%;
  padding: 14px;
  background: #faa62e;
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.15s, transform 0.1s;
}

.cb-cta:hover {
  background: #e8941a;
  transform: translateY(-1px);
}

.cb-cta:active {
  transform: translateY(0);
}

/* Transition */
.cb-fade-enter-active,
.cb-fade-leave-active {
  transition: opacity 0.25s ease;
}

.cb-fade-enter-from,
.cb-fade-leave-to {
  opacity: 0;
}
</style>
