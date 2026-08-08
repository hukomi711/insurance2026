function createDedupId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 12 ) }`;
}

function withDedupPayload(payload = {}) {
    if (payload.client_dedup_id || payload.event_id) {
        return payload;
    }

    const dedupId = createDedupId();
    return {
        ...payload,
        client_dedup_id: dedupId,
        event_id: dedupId,
    };
}

export function trackSnapchatEvent(eventName, payload = {}) {
    if (typeof window === 'undefined') return;

    if (typeof window.snaptr === 'function') {
        try {
            window.snaptr( 'track', eventName, payload );
        } catch (error) {
            console.warn('[Snapchat Pixel] Failed to track event:', eventName, error);
        }
    }
}

export function trackSnapchatPageView() {
    trackSnapchatEvent('PAGE_VIEW');
}

export function trackSnapchatQuoteStart(payload = {}) {
    trackSnapchatEvent( 'CUSTOM_EVENT_1', withDedupPayload( {
        event_tag: 'START_QUOTE',
        ...payload,
    } ) );
}

export function trackSnapchatQuoteSubmit(payload = {}) {
    trackSnapchatEvent( 'CUSTOM_EVENT_2', withDedupPayload( {
        event_tag: 'QUOTE_SUBMITTED',
        ...payload,
    } ) );
}

export function trackSnapchatStartCheckout(payload = {}) {
    trackSnapchatEvent( 'START_CHECKOUT', withDedupPayload( payload ) );
}

export function trackSnapchatPurchase(payload = {}) {
    trackSnapchatEvent( 'PURCHASE', withDedupPayload( payload ) );
}
