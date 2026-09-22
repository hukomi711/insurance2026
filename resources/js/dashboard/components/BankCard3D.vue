<template>
  <div class="bank-card-3d" :class="brandClass" dir="ltr">
    <!-- Optional in-card status pill (top-right corner) -->
    <div
      v-if="statusPill"
      class="absolute right-3 top-3 z-10 rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider"
      :class="statusPill.classes"
    >
      {{ statusPill.label }}
    </div>

    <!-- ─── Top row: Bank logo + Currency ─── -->
    <div class="card-top">
      <img
        v-if="resolvedBankLogo"
        :src="resolvedBankLogo"
        :alt="displayBankNameAr || displayBankName"
        class="bank-logo"
      />
      <div v-else class="bank-fallback">{{ displayBankNameAr || displayBankName || '—' }}</div>
      <div class="currency-pill">SAR</div>
    </div>

    <!-- ─── Card number + Expiry ─── -->
    <div class="card-pan-row">
      <div class="card-pan">{{ formattedCardNumber }}</div>
      <div class="card-expiry">{{ expiry || '—' }}</div>
    </div>

    <!-- ─── Holder + CVV ─── -->
    <div class="card-mid-row">
      <div class="card-holder">{{ holderName || '—' }}</div>
      <div v-if="cvv" class="card-cvv">
        <div class="cvv-label">CVV</div>
        <div class="cvv-val">{{ cvv }}</div>
      </div>
    </div>

    <!-- ─── Flag + Network + Card type/level ─── -->
    <div class="card-bottom">
      <div class="flag-pill" title="SA"></div>
      <div class="brand-block">
        <template v-if="isMada && secondaryNetworkLogo">
          <img :src="madaLogoUrl" alt="mada" class="mada-logo" />
          <img :src="secondaryNetworkLogo" :alt="secondaryNetworkName" class="secondary-logo" />
        </template>
        <span v-else-if="resolvedScheme === 'visa'" class="visa-mark">VISA</span>
        <span v-else-if="resolvedScheme === 'mastercard'" class="mc-mark"></span>
        <span v-else-if="resolvedScheme === 'amex'" class="amex-mark">AMEX</span>
        <img v-else-if="resolvedNetworkLogo" :src="resolvedNetworkLogo" :alt="resolvedNetworkName" class="mada-logo" />
        <span class="category-text">{{ categoryLine }}</span>
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
  status: { type: String, default: '' },
  cvv: { type: String, default: '' },
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

// ── Formatted card number ────────────────────────────────────────
const formattedCardNumber = computed(() => {
  if (!props.cardNumber) return '—';
  const cleaned = props.cardNumber.replace(/\s/g, '').replace(/[*•xX]/g, '');
  if (!cleaned) return '—';
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

// ── Brand class (flat pastel background matching the bank-report design) ──
const brandClass = computed(() => {
  switch (resolvedScheme.value) {
    case 'visa': return 'brand-visa';
    case 'mastercard': return 'brand-mastercard';
    case 'mada': return 'brand-mada';
    case 'amex': return 'brand-amex';
    default: return 'brand-mada';
  }
});

// ── Card type/level line (e.g. "DEBIT • TITANIUM") ──────────────────
const categoryLine = computed(() => {
  const parts = [
    (displayCardType.value || '').toUpperCase(),
    (displayCardLevel.value || '').toUpperCase(),
  ].filter(Boolean);
  return parts.join(' • ');
});

// ── In-card status pill (pending/approved/rejected) ─────────────
const statusPill = computed(() => {
  const s = (props.status || '').toLowerCase();
  if (!s) return null;
  if (s === 'pending')                       return { label: 'Pending',  classes: 'bg-amber-100 text-amber-700 ring-1 ring-amber-300' };
  if (s === 'approved' || s === 'verified')  return { label: 'Approved', classes: 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-300' };
  if (s === 'rejected' || s === 'failed')    return { label: 'Rejected', classes: 'bg-rose-100 text-rose-700 ring-1 ring-rose-300' };
  return null;
});
</script>

<style scoped>
.bank-card-3d {
  --card-bg-start: var(--payment-card-bg-start, #edf9f1);
  --card-bg-end: var(--payment-card-bg-end, #dfeee4);
  --card-border: var(--payment-card-border, rgba(15, 23, 42, 0.08));
  --card-sheen: var(--payment-card-sheen, rgba(255, 255, 255, 0.32));
  --card-sheen-strong: var(--payment-card-sheen-strong, rgba(255, 255, 255, 0.52));
  --card-text: var(--payment-card-text, #122033);
  --card-muted: var(--payment-card-muted, #5d6b82);
  --card-pill: var(--payment-card-pill, rgba(255, 255, 255, 0.72));
  --card-shadow: var(--payment-card-shadow, 0 12px 28px rgba(15, 23, 42, 0.12));

  width: 100%;
  max-width: 400px;
  aspect-ratio: 1.586 / 1;
  display: flex;
  flex-direction: column;
  border-radius: 18px;
  padding: 15px 16px 12px;
  direction: ltr;
  font-family: "Tahoma", "Arial", sans-serif;
  color: var(--card-text);
  border: 1px solid var(--card-border);
  box-shadow: var(--card-shadow);
  position: relative;
  overflow: hidden;
  isolation: isolate;
}

.bank-card-3d::before,
.bank-card-3d::after {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.bank-card-3d::before {
  background: linear-gradient(135deg, var(--card-sheen) 0%, transparent 36%, transparent 64%, var(--card-sheen-strong) 100%);
  transform: translateX(8%);
}

.bank-card-3d::after {
  inset: auto -18% -46% auto;
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  filter: blur(8px);
}

.bank-card-3d.brand-mada {
  background: linear-gradient(180deg, var(--payment-card-bg-start, #e9f5ec) 0%, var(--payment-card-bg-end, #d8ecdc) 100%);
}
.bank-card-3d.brand-visa {
  background: linear-gradient(180deg, #eef4ff 0%, #dfeafb 100%);
}
.bank-card-3d.brand-mastercard {
  background: linear-gradient(180deg, #fff1f1 0%, #ffe1e1 100%);
}
.bank-card-3d.brand-amex {
  background: linear-gradient(180deg, #edf5ff 0%, #dfeeff 100%);
}

.card-top,
.card-pan-row,
.card-mid-row,
.card-bottom {
  position: relative;
  z-index: 1;
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.bank-logo {
  max-height: 28px;
  max-width: 170px;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(15, 23, 42, 0.08));
}

.bank-fallback {
  font-weight: 800;
  font-size: 13px;
  letter-spacing: 0.04em;
  color: var(--card-text);
}

.currency-pill {
  background: var(--card-pill);
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 8px;
  padding: 3px 10px;
  font-size: 10px;
  line-height: 1.4;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: var(--card-text);
  text-transform: uppercase;
}

.card-pan-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  gap: 8px;
}

.card-pan {
  font-size: 19px;
  font-weight: 800;
  letter-spacing: 1.7px;
  font-family: "Courier New", monospace;
  color: var(--card-text);
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.2);
}

.card-expiry {
  font-size: 12px;
  font-weight: 700;
  color: var(--card-text);
  opacity: 0.9;
}

.card-mid-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 20px;
  gap: 12px;
}

.card-holder {
  font-size: 12px;
  font-weight: 700;
  max-width: 220px;
  word-break: break-word;
  color: var(--card-text);
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.card-cvv {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 48px;
}

.card-cvv .cvv-label {
  font-size: 8px;
  color: var(--card-muted);
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.card-cvv .cvv-val {
  font-size: 12px;
  font-weight: 800;
  color: var(--card-text);
}

.card-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.flag-pill {
  display: inline-block;
  width: 23px;
  height: 15px;
  border-radius: 4px;
  background: linear-gradient(180deg, #0a5c36 0%, #0b7744 100%);
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.flag-pill::after {
  content: "";
  position: absolute;
  left: 3px;
  top: 4px;
  width: 15px;
  height: 6px;
  background: rgba(255, 255, 255, 0.72);
  border-radius: 1px;
}

.brand-block {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: var(--card-text);
}

.mada-logo {
  height: 15px;
  filter: drop-shadow(0 1px 3px rgba(15, 23, 42, 0.08));
}

.secondary-logo {
  height: 14px;
  max-width: 50px;
  object-fit: contain;
}

.visa-mark {
  font-family: "Arial Black", sans-serif;
  font-style: italic;
  font-weight: 900;
  font-size: 18px;
  color: #1a1f71;
  letter-spacing: -1px;
}

.mc-mark {
  position: relative;
  width: 36px;
  height: 18px;
  display: inline-block;
}

.mc-mark::before,
.mc-mark::after {
  content: "";
  position: absolute;
  top: 0;
  width: 18px;
  height: 18px;
  border-radius: 50%;
}

.mc-mark::before {
  left: 0;
  background: #eb001b;
}

.mc-mark::after {
  left: 12px;
  background: #f79e1b;
  opacity: 0.85;
  mix-blend-mode: multiply;
}

.amex-mark {
  background: #006fcf;
  color: #fff;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: "Arial Black", sans-serif;
  font-size: 11px;
  letter-spacing: 0.04em;
}

.category-text {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--card-muted);
}
</style>
