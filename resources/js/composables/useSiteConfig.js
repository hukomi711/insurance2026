import { reactive, readonly, computed } from 'vue';
import request from '@/api/request';
import logger from '@/utils/logger';

/**
 * Site-wide public configuration (contact info, feature flags).
 *
 * Loaded once per SPA boot from GET /api/site-config. Subsequent
 * components share the same singleton state without refetching.
 */

const state = reactive({
    whatsapp: {
        enabled: false,
        number: '',
        wa_link: null,
        message: '',
    },
    support_phone: '',
    contact_email: '',
    isLoaded: false,
    isLoading: false,
    error: null,
});

let inflight = null;

async function load() {
    if (state.isLoaded || state.isLoading) {
        return inflight;
    }
    state.isLoading = true;
    state.error = null;

    inflight = request
        .get('/api/site-config')
        .then((res) => {
            const data = res?.data || {};
            if (data.whatsapp) {
                state.whatsapp.enabled = !!data.whatsapp.enabled;
                state.whatsapp.number = data.whatsapp.number || '';
                state.whatsapp.wa_link = data.whatsapp.wa_link || null;
                state.whatsapp.message = data.whatsapp.message || '';
            }
            state.support_phone = data.support_phone || '';
            state.contact_email = data.contact_email || '';
            state.isLoaded = true;
        })
        .catch((err) => {
            state.error = err?.message || 'failed to load site config';
            logger.warn('[useSiteConfig] load failed', err);
        })
        .finally(() => {
            state.isLoading = false;
            inflight = null;
        });

    return inflight;
}

export function useSiteConfig() {
    return {
        state: readonly(state),
        whatsappEnabled: computed(() => state.whatsapp.enabled && !!state.whatsapp.wa_link),
        whatsappLink: computed(() => state.whatsapp.wa_link),
        load,
    };
}
