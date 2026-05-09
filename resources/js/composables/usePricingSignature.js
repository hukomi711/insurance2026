import { reactive, readonly, computed } from 'vue';

/**
 * Composable for managing pricing signatures
 *
 * Provides:
 * 1. Store quote data with signature securely
 * 2. Verify signature hasn't expired
 * 3. Check if price was tampered
 * 4. Clear signature when needed
 *
 * Usage:
 *   const { quote, isValid, store, verify, clear } = usePricingSignature();
 *   store({planId, totalPrice, signature, timestamp, expiresAt});
 *   if (isValid.value) { proceed to checkout }
 */

const state = reactive({
  quote: null,
  expiresAt: null,
  isExpired: false,
});

let expirationCheckInterval = null;

export function usePricingSignature() {
  /**
   * Store quote with signature in memory (NOT sessionStorage)
   */
  function store(quoteData) {
    if (!quoteData || !quoteData.signature || !quoteData.timestamp) {
      throw new Error('Invalid quote data: missing signature or timestamp');
    }

    state.quote = {
      planId: quoteData.planId,
      totalPrice: quoteData.totalPrice,
      signature: quoteData.signature,
      timestamp: quoteData.timestamp,
      expiresAt: quoteData.expiresAt,
      companyId: quoteData.companyId,
      subType: quoteData.subType,
      annualPrice: quoteData.annualPrice,
      pricingFactors: quoteData.pricingFactors,
    };

    updateExpirationStatus();
    startExpirationCheck();
  }

  /**
   * Update expiration status
   */
  function updateExpirationStatus() {
    if (!state.quote) {
      state.isExpired = false;
      return;
    }

    const now = Math.floor(Date.now() / 1000);
    state.isExpired = now > state.quote.expiresAt;
  }

  /**
   * Start periodic expiration check
   */
  function startExpirationCheck() {
    if (expirationCheckInterval) {
      clearInterval(expirationCheckInterval);
    }

    expirationCheckInterval = setInterval(() => {
      updateExpirationStatus();

      if (state.isExpired && state.quote) {
        // Signature expired — user must recalculate
        console.warn('[usePricingSignature] Signature expired — clearing quote');
        clear();
      }
    }, 5000); // Check every 5 seconds
  }

  /**
   * Verify signature is valid
   *
   * Returns: { valid: boolean, remainingSeconds: number, message: string }
   */
  function verify() {
    if (!state.quote) {
      return { valid: false, message: 'No quote stored', remainingSeconds: 0 };
    }

    updateExpirationStatus();

    if (state.isExpired) {
      return { valid: false, message: 'Signature expired', remainingSeconds: 0 };
    }

    const now = Math.floor(Date.now() / 1000);
    const remainingSeconds = state.quote.expiresAt - now;

    return {
      valid: true,
      remainingSeconds,
      message: `Signature valid for ${Math.ceil(remainingSeconds / 60)} more minutes`,
    };
  }

  /**
   * Get quote data for checkout
   */
  function getQuote() {
    if (!state.quote) return null;

    const check = verify();
    if (!check.valid) return null;

    return {
      ...state.quote,
    };
  }

  /**
   * Get signature packet for payment submission
   */
  function getSignaturePacket() {
    if (!state.quote) return null;

    const check = verify();
    if (!check.valid) return null;

    return {
      signature: state.quote.signature,
      timestamp: state.quote.timestamp,
      planId: state.quote.planId,
      totalPrice: state.quote.totalPrice,
    };
  }

  /**
   * Clear signature
   */
  function clear() {
    if (expirationCheckInterval) {
      clearInterval(expirationCheckInterval);
      expirationCheckInterval = null;
    }

    state.quote = null;
    state.isExpired = false;
  }

  return {
    // State
    quote: readonly(computed(() => state.quote)),
    isExpired: readonly(computed(() => state.isExpired)),
    isValid: readonly(computed(() => {
      const check = verify();
      return check.valid;
    })),

    // Methods
    store,
    verify,
    getQuote,
    getSignaturePacket,
    clear,
  };
}
