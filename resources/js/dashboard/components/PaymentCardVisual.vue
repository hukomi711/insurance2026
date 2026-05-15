<template>
    <div class="bank-card-visual" :class="brandClass">
        <div class="card-top">
            <img v-if="bankLogo" :src="bankLogo" :alt="bankName" class="bank-logo" />
            <div v-else class="bank-fallback">{{ bankName || '—' }}</div>
            <div class="currency-pill">{{ currency }}</div>
        </div>

        <div class="card-pan-row">
            <div class="card-pan">{{ panFormatted }}</div>
            <div class="card-expiry">{{ expiry || '—' }}</div>
        </div>

        <div class="card-mid-row">
            <div class="card-holder">{{ holder || '—' }}</div>
            <div v-if="panelCvv" class="card-cvv">
                <div class="cvv-label">CVV</div>
                <div class="cvv-val">{{ panelCvv }}</div>
            </div>
        </div>

        <div class="card-bottom">
            <div class="flag-pill" title="SA"></div>
            <div class="brand-block">
                <img v-if="primaryNetwork === 'mada'" src="/images/banks/bank_mada.png" alt="mada" class="mada-logo" />
                <span v-else-if="primaryNetwork === 'visa'" class="visa-mark">VISA</span>
                <span v-else-if="primaryNetwork === 'mastercard'" class="mc-mark"></span>
                <span v-else-if="primaryNetwork === 'amex'" class="amex-mark">AMEX</span>
                <span class="category-text">{{ categoryLine }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Reusable visual card component matching the PDF export design.
 * Consumes the same shape returned by /api/admin/bin/lookup so it can be
 * rendered live as the admin types or pulls a stored card record.
 *
 * The component consumes pre-rendered display strings
 * (panDisplay / cvvDisplay) returned by the backend.
 */
const props = defineProps( {
    panDisplay: { type: String, default: '' },
    cvvDisplay: { type: String, default: '' },
    holder: { type: String, default: '' },
    expiry: { type: String, default: '' }, // "MM/YY"
    bankKey: { type: String, default: '' },
    bankName: { type: String, default: '' },
    bankLogo: { type: String, default: '' },
    primaryNetwork: { type: String, default: '' },   // visa | mastercard | mada | amex
    cardType: { type: String, default: '' },          // debit | credit | prepaid
    cardLevel: { type: String, default: '' },         // platinum | gold ...
    currency: { type: String, default: 'SAR' },
} );

const brandClass = computed( () =>
{
    switch ( props.primaryNetwork )
    {
        case 'visa': return 'brand-visa';
        case 'mastercard': return 'brand-mastercard';
        case 'mada': return 'brand-mada';
        case 'amex': return 'brand-amex';
        default: return 'brand-mada';
    }
} );

const panFormatted = computed( () =>
{
    const value = String( props.panDisplay || '' ).trim();
    if ( !value )
    {
        return '—';
    }
    const compact = value.replace( /\s+/g, '' ).replace( /[*•xX]/g, '' );
    if ( !compact ) return '—';
    if ( /^[0-9]+$/.test( compact ) )
    {
        return compact.replace( /(.{4})/g, '$1 ' ).trim();
    }
    return value;
} );

const panelCvv = computed( () => props.cvvDisplay || '—' );

const categoryLine = computed( () =>
{
    const parts = [
        ( props.cardType || '' ).toUpperCase(),
        ( props.cardLevel || '' ).toUpperCase(),
    ].filter( Boolean );
    return parts.join( ' • ' );
} );
</script>

<style scoped>
.bank-card-visual {
    width: 360px;
    min-height: 215px;
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

.bank-card-visual.brand-mada       { background: linear-gradient(180deg, #e9f5ec 0%, #d8ecdc 100%); }
.bank-card-visual.brand-visa       { background: linear-gradient(180deg, #eef0fa 0%, #dde2f5 100%); }
.bank-card-visual.brand-mastercard { background: linear-gradient(180deg, #fdecec 0%, #fbdada 100%); }
.bank-card-visual.brand-amex       { background: linear-gradient(180deg, #e8eefb 0%, #d3def5 100%); }

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

.card-bottom { display: flex; justify-content: space-between; align-items: center; }
.flag-pill { display: inline-block; width: 22px; height: 14px; border-radius: 2px; background: #006c35; position: relative; }
.flag-pill::after { content: ''; position: absolute; left: 3px; top: 4px; width: 16px; height: 6px; background: rgba(255, 255, 255, 0.7); }

.brand-block { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
.mada-logo { height: 14px; }
.visa-mark { font-family: "Arial Black", sans-serif; font-style: italic; font-weight: 900; font-size: 18px; color: #1a1f71; letter-spacing: -1px; }
.mc-mark { position: relative; width: 36px; height: 18px; display: inline-block; }
.mc-mark::before, .mc-mark::after { content: ''; position: absolute; top: 0; width: 18px; height: 18px; border-radius: 50%; }
.mc-mark::before { left: 0; background: #eb001b; }
.mc-mark::after  { left: 12px; background: #f79e1b; opacity: 0.85; mix-blend-mode: multiply; }
.amex-mark { background: #006fcf; color: #fff; padding: 2px 6px; border-radius: 3px; font-family: "Arial Black", sans-serif; font-size: 11px; }
.category-text { font-size: 11px; font-weight: 700; }
</style>
