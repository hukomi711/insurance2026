<template>
  <div class="bank-card-3d" dir="ltr">
    <div
      class="card-face relative flex flex-col overflow-hidden rounded-2xl border text-white shadow-2xl"
      :class="cardBorderClass"
      :style="cardStyle"
    >
      <!-- Holographic shimmer overlay -->
      <div class="shimmer-overlay pointer-events-none absolute inset-0 z-20 rounded-2xl" />

      <!-- Decorative background shapes -->
      <div class="pointer-events-none absolute -right-8 -top-8 h-44 w-44 rounded-full bg-white/[0.07] blur-xl" />
      <div class="pointer-events-none absolute -bottom-12 -left-12 h-56 w-56 rounded-full bg-white/[0.04] blur-lg" />
      <div class="pointer-events-none absolute left-1/2 top-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/[0.02] blur-2xl" />
      <!-- Diagonal accent line -->
      <div class="pointer-events-none absolute -right-20 top-10 h-[200%] w-24 rotate-[25deg] bg-white/[0.03]" />

      <!-- ─── Top row: Bank logo + Contactless ─── -->
      <div class="relative z-10 flex items-center justify-between px-6 pt-5">
        <!-- Bank logo -->
        <div>
          <img
            v-if="resolvedBankLogo"
            :src="resolvedBankLogo"
            :alt="displayBankName"
            class="h-10 w-auto max-w-[130px] object-contain brightness-0 invert drop-shadow-[0_1px_6px_rgba(255,255,255,0.3)]"
            width="130" height="40"
          />
          <div v-else-if="displayBankName">
            <div class="text-[15px] font-bold tracking-wide drop-shadow-lg">{{ displayBankName }}</div>
            <div v-if="displayBankNameAr && displayBankNameAr !== displayBankName" class="text-[10px] opacity-70">{{ displayBankNameAr }}</div>
          </div>
          <div v-else class="text-[15px] font-semibold tracking-wide opacity-50 drop-shadow">Bank Card</div>
        </div>
        <!-- Contactless icon -->
        <svg class="h-6 w-6 opacity-30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M8.5 11a3.5 3.5 0 015 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <path d="M6.5 9a6 6 0 018.5 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <path d="M4.5 7a8.5 8.5 0 0112 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          <circle cx="11" cy="13" r="1" fill="currentColor" />
        </svg>
      </div>

      <!-- ─── Card number (centered vertically) ─── -->
      <div class="relative z-10 flex flex-1 items-center px-6">
        <div class="card-number font-mono text-[1.35rem] font-bold tracking-[0.2em] drop-shadow-lg" dir="ltr">
          {{ formattedCardNumber }}
        </div>
      </div>

      <!-- ─── Bottom: Holder + Expiry ─── -->
      <div class="relative z-10 px-6 pb-4">
        <div class="flex items-end gap-5">
          <div class="min-w-0 flex-1">
            <div class="text-[7px] uppercase tracking-[0.18em] opacity-40">Card Holder</div>
            <div class="truncate text-[13px] font-semibold tracking-wide drop-shadow-lg">{{ holderName || '—' }}</div>
          </div>
          <div class="shrink-0 text-center">
            <div class="text-[7px] uppercase tracking-[0.18em] opacity-40">Expires</div>
            <div class="font-mono text-[13px] font-semibold drop-shadow-lg">{{ expiry || '—' }}</div>
          </div>
        </div>
        <!-- Card type + Network logo -->
        <div class="mt-2 flex items-center justify-between">
          <div>
            <span
              v-if="displayCardType && displayCardType !== 'unknown'"
              class="inline-block rounded-full bg-white/10 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-widest"
            >
              {{ displayCardType }}
              <span v-if="displayCardLevel" class="ml-1 opacity-60">· {{ displayCardLevel }}</span>
            </span>
          </div>
          <div class="flex items-center gap-1.5">
            <template v-if="isMada && secondaryNetworkLogo">
              <img :src="madaLogoUrl" alt="mada" class="h-5 w-auto drop-shadow-lg" width="40" height="20" />
              <img :src="secondaryNetworkLogo" :alt="secondaryNetworkName" class="h-4 w-auto drop-shadow-lg" width="32" height="16" />
            </template>
            <template v-else-if="resolvedNetworkLogo">
              <img :src="resolvedNetworkLogo" :alt="resolvedNetworkName" class="h-6 w-auto drop-shadow-lg" width="48" height="24" />
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  useCardBranding,
  getCardBrand,
  networkLogos,
  bankLogos,
  BANKS,
} from '@/composables/useCardBranding';

const props = defineProps({
  cardNumber: { type: String, default: '' },
  holderName: { type: String, default: '' },
  expiry: { type: String, default: '' },
  bankName: { type: String, default: '' },
  bankNameArabic: { type: String, default: '' },
  scheme: { type: String, default: '' },
  cardType: { type: String, default: '' },
  cardLevel: { type: String, default: '' },
});

// ── Branding from BIN ───────────────────────────────────────────────
const bin = computed(() => (props.cardNumber || '').replace(/\s/g, '').substring(0, 6));
const {
  brand,
  networkLogo,
  networkName,
  bankLogo,
  bankName: detectedBankName,
} = useCardBranding(bin);

// ── Resolved scheme (never show "unknown" if detectable from card number)
const resolvedScheme = computed(() => {
  const propScheme = (props.scheme || '').toLowerCase();
  if (propScheme && propScheme !== 'unknown') return propScheme;
  if (brand.value && brand.value !== 'unknown') return brand.value;
  // Last resort: detect from full card number
  const fromNumber = getCardBrand(props.cardNumber);
  if (fromNumber && fromNumber !== 'unknown') return fromNumber;
  return '';
});

// ── Formatted card number ───────────────────────────────────────────
const formattedCardNumber = computed(() => {
  if (!props.cardNumber) return '•••• •••• •••• ••••';
  const cleaned = props.cardNumber.replace(/\s/g, '');
  return cleaned.replace(/(.{4})/g, '$1 ').trim();
});

// ── Display bank name (prefer BIN-detected, fall back to props) ────
const SCHEME_NAMES = ['visa', 'mastercard', 'mada', 'amex', 'discover', 'unionpay', 'unknown', 'bank'];

const displayBankName = computed(() => {
  if (detectedBankName.value) return detectedBankName.value;
  if (props.bankNameArabic) return props.bankNameArabic;
  const name = (props.bankName || '').trim();
  if (name && !SCHEME_NAMES.includes(name.toLowerCase())) return name;
  return '';
});

const displayBankNameAr = computed(() => {
  if (detectedBankName.value) return detectedBankName.value;
  return props.bankNameArabic || '';
});

const displayCardType = computed(() => {
  const t = (props.cardType || '').toLowerCase();
  if (!t || t === 'unknown') return resolvedScheme.value === 'mada' ? 'debit' : '';
  return props.cardType;
});

const displayCardLevel = computed(() => {
  if (!props.cardLevel) return '';
  const lower = props.cardLevel.toLowerCase();
  if (SCHEME_NAMES.includes(lower)) return '';
  return props.cardLevel;
});

// ── Resolve logos (prefer BIN-detected, fall back to props) ─────────
const resolvedNetworkLogo = computed(() => {
  if (networkLogo.value) return networkLogo.value;
  const s = resolvedScheme.value;
  return networkLogos[s] || null;
});

const resolvedNetworkName = computed(() => {
  if (networkName.value) return networkName.value;
  const names = { visa: 'Visa', mastercard: 'Mastercard', mada: 'مدى', amex: 'Amex' };
  return names[resolvedScheme.value] || '';
});

// English bank name → key mapping for fallback
const BANK_EN_MAP = {
  'rajhi': ['rajhi', 'al rajhi'],
  'ahli': ['ahli', 'snb', 'al ahli', 'national commercial'],
  'inma': ['inma', 'alinma'],
  'sabb': ['sabb', 'saudi british'],
  'jazira': ['jazira', 'aljazira'],
  'riyad': ['riyad'],
  'bilad': ['bilad', 'albilad'],
  'anb': ['anb', 'arab national'],
  'saib': ['saib', 'investment'],
  'bsf': ['bsf', 'fransi', 'saudi fransi'],
};

const resolvedBankLogo = computed(() => {
  if (bankLogo.value) return bankLogo.value;

  if (props.bankNameArabic) {
    for (const [key, info] of Object.entries(BANKS)) {
      if (props.bankNameArabic.includes(info.nameAr) || info.nameAr.includes(props.bankNameArabic)) {
        return bankLogos[key] || null;
      }
    }
  }

  if (props.bankName) {
    const lower = props.bankName.toLowerCase();
    for (const [key, keywords] of Object.entries(BANK_EN_MAP)) {
      if (keywords.some(kw => lower.includes(kw))) {
        return bankLogos[key] || null;
      }
    }
  }

  return null;
});

// ── Mada dual-logo support ──────────────────────────────────────────
const isMada = computed(() => resolvedScheme.value === 'mada');

const madaLogoUrl = computed(() => networkLogos.mada);

const secondaryNetworkLogo = computed(() => {
  if (!isMada.value) return null;
  const cleaned = (props.cardNumber || '').replace(/\s/g, '');
  if (cleaned.startsWith('4')) return networkLogos.visa;
  if (/^(5[1-5]|2[2-7])/.test(cleaned)) return networkLogos.mastercard;
  return null;
});

const secondaryNetworkName = computed(() => {
  if (!secondaryNetworkLogo.value) return '';
  const cleaned = (props.cardNumber || '').replace(/\s/g, '');
  if (cleaned.startsWith('4')) return 'Visa';
  return 'Mastercard';
});

// ── Unified premium gradient ────────────────────────────────────────
const NETWORK_GRADIENTS = {
  visa:       'linear-gradient(135deg, #0c1445 0%, #1a3a8a 40%, #1d4ed8 100%)',
  mastercard: 'linear-gradient(135deg, #1a0a2e 0%, #6b1d5e 40%, #cc2d4a 100%)',
  mada:       'linear-gradient(135deg, #0a2e1a 0%, #0d5e3a 40%, #059669 100%)',
  amex:       'linear-gradient(135deg, #1a1a2e 0%, #2d2d5e 40%, #4f46e5 100%)',
  default:    'linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #2563eb 100%)',
};

const cardStyle = computed(() => ({
  background: NETWORK_GRADIENTS[resolvedScheme.value] || NETWORK_GRADIENTS.default,
}));

const cardBorderClass = computed(() => {
  const map = {
    visa:       'border-blue-500/20',
    mastercard: 'border-pink-500/20',
    mada:       'border-emerald-500/20',
    amex:       'border-indigo-500/20',
  };
  return map[resolvedScheme.value] || 'border-white/[0.08]';
});
</script>

<style scoped>
.bank-card-3d {
  perspective: 1200px;
  max-width: 400px;
  /* Credit card aspect ratio: 85.6mm × 53.98mm ≈ 1.586:1 */
  aspect-ratio: 1.586 / 1;
}

.card-face {
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
  transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
}

.bank-card-3d:hover .card-face {
  transform: rotateY(-5deg) rotateX(3deg) scale(1.03);
}

/* Card number subtle text shadow */
.card-number {
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

/* Holographic shimmer effect */
.shimmer-overlay {
  background: linear-gradient(
    105deg,
    transparent 30%,
    rgba(255, 255, 255, 0.06) 45%,
    rgba(255, 255, 255, 0.12) 50%,
    rgba(255, 255, 255, 0.06) 55%,
    transparent 70%
  );
  background-size: 200% 100%;
  animation: shimmer 4s ease-in-out infinite;
}

@keyframes shimmer {
  0%, 100% { background-position: 200% 0; }
  50%      { background-position: -200% 0; }
}
</style>
