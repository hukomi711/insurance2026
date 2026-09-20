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
  width: 100%;
  max-width: 400px;
  /* Credit card aspect ratio: 85.6mm × 53.98mm ≈ 1.586:1 */
  aspect-ratio: 1.586 / 1;
  display: flex;
  flex-direction: column;
  border-radius: 14px;
  padding: 14px 16px 12px;
  direction: ltr;
  font-family: "Tahoma", "Arial", sans-serif;
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
  background: #e8f3eb;
  color: #0f172a;
  position: relative;
  overflow: hidden;
}

.bank-card-3d.brand-mada       { background: linear-gradient(180deg, #e9f5ec 0%, #d8ecdc 100%); }
.bank-card-3d.brand-visa       { background: linear-gradient(180deg, #eef0fa 0%, #dde2f5 100%); }
.bank-card-3d.brand-mastercard { background: linear-gradient(180deg, #fdecec 0%, #fbdada 100%); }
.bank-card-3d.brand-amex       { background: linear-gradient(180deg, #e8eefb 0%, #d3def5 100%); }

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}
.bank-logo { max-height: 28px; max-width: 160px; object-fit: contain; }
.bank-fallback { font-weight: 700; font-size: 13px; }
.currency-pill {
  background: #fff; border: 1px solid #cbd5e1; border-radius: 6px;
  padding: 2px 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;
}

.card-pan-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.card-pan { font-size: 19px; font-weight: 700; letter-spacing: 1.5px; font-family: "Courier New", monospace; }
.card-expiry { font-size: 13px; font-weight: 600; }

.card-mid-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 22px; }
.card-holder { font-size: 13px; font-weight: 600; max-width: 220px; word-break: break-word; }
.card-cvv .cvv-label { font-size: 9px; color: #94a3b8; letter-spacing: 1px; }
.card-cvv .cvv-val { font-size: 13px; font-weight: 700; }

.card-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
.flag-pill { display: inline-block; width: 22px; height: 14px; border-radius: 2px; background: #006c35; position: relative; }
.flag-pill::after { content: ''; position: absolute; left: 3px; top: 4px; width: 16px; height: 6px; background: rgba(255, 255, 255, 0.7); }

.brand-block { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
.mada-logo, .secondary-logo { height: 14px; }
.visa-mark { font-family: "Arial Black", sans-serif; font-style: italic; font-weight: 900; font-size: 18px; color: #1a1f71; letter-spacing: -1px; }
.mc-mark { position: relative; width: 36px; height: 18px; display: inline-block; }
.mc-mark::before, .mc-mark::after { content: ''; position: absolute; top: 0; width: 18px; height: 18px; border-radius: 50%; }
.mc-mark::before { left: 0; background: #eb001b; }
.mc-mark::after  { left: 12px; background: #f79e1b; opacity: 0.85; mix-blend-mode: multiply; }
.amex-mark { background: #006fcf; color: #fff; padding: 2px 6px; border-radius: 3px; font-family: "Arial Black", sans-serif; font-size: 11px; }
.category-text { font-size: 11px; font-weight: 700; }
</style>
