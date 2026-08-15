import { reactive, readonly, computed } from 'vue';
import request from '@/api/request';
import { isCustomerBlocked, isCustomerBlockedError } from '@/utils/customerBlock';

/**
 * Composable for managing pricing constants/factors
 *
 * Provides:
 * 1. Load constants from Backend endpoint
 * 2. Store in reactive state
 * 3. Check version mismatch
 * 4. Notify if version changes
 *
 * Usage:
 *   const { constants, version, isLoading, load } = usePricingConstants();
 *   await load();
 *   const basePrice = constants.value.base_premiums[subType];
 */

const state = reactive({
  constants: null,
  version: null,
  hash: null,
  lastUpdated: null,
  isLoading: false,
  error: null,
});

let isInitialized = false;

export function usePricingConstants() {
  /**
   * Load pricing constants from Backend
   */
  async function load() {
    if (isInitialized && state.constants) {
      return;
    }

    if (isCustomerBlocked()) {
      return;
    }

    state.isLoading = true;
    state.error = null;

    try {
      const { data: response } = await request.get('/pricing/constants');

      if (!response || !response.constants) {
        throw new Error('Invalid pricing constants response');
      }

      state.constants = response.constants;
      state.version = response.version;
      state.hash = response.hash;
      state.lastUpdated = response.lastUpdated;
      isInitialized = true;
    } catch (err) {
      if (isCustomerBlockedError(err)) {
        return;
      }

      state.error = err.message || 'Failed to load pricing constants';
      console.error('[usePricingConstants] Error loading constants:', err);
    } finally {
      state.isLoading = false;
    }
  }

  /**
   * Verify if client version matches server version
   *
   * Returns: { match: boolean, message: string }
   */
  async function verify() {
    if (!state.version || !state.hash) {
      return { match: false, message: 'Constants not loaded' };
    }

    try {
      const { data: response } = await request.post('/pricing/constants/verify', {
        version: state.version,
        hash: state.hash,
      });

      return {
        match: response.match,
        message: response.message,
      };
    } catch (err) {
      if (isCustomerBlockedError(err)) {
        return { match: false, message: 'Blocked customer' };
      }

      console.error('[usePricingConstants] Error verifying constants:', err);
      return { match: false, message: 'Verification failed' };
    }
  }

  /**
   * Get specific constant by path
   * Example: getConstant('base_premiums.comprehensive')
   */
  function getConstant(path) {
    if (!state.constants) return null;

    const keys = path.split('.');
    let value = state.constants;

    for (const key of keys) {
      value = value?.[key];
      if (value === undefined) return null;
    }

    return value;
  }

  return {
    // State
    constants: readonly(computed(() => state.constants)),
    version: readonly(computed(() => state.version)),
    hash: readonly(computed(() => state.hash)),
    lastUpdated: readonly(computed(() => state.lastUpdated)),
    isLoading: readonly(computed(() => state.isLoading)),
    error: readonly(computed(() => state.error)),

    // Methods
    load,
    verify,
    getConstant,
  };
}
