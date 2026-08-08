export function trackSnapchatEvent(eventName, payload = {}) {
    if (typeof window === 'undefined') return;

    if (typeof window.snaptr === 'function') {
        try {
            window.snaptr('track', eventName, payload);
        } catch (error) {
            console.warn('[Snapchat Pixel] Failed to track event:', eventName, error);
        }
    }
}

export function trackSnapchatPageView() {
    trackSnapchatEvent('PAGE_VIEW');
}

export function trackSnapchatQuoteStart() {
    trackSnapchatEvent('START_QUOTE');
}

export function trackSnapchatQuoteSubmit() {
    trackSnapchatEvent('SUBMIT_FORM');
}

export function trackSnapchatPurchase() {
    trackSnapchatEvent('PURCHASE');
}
