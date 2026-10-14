const isValidPixelId = (value) => /^\d{5,20}$/.test(String(value ?? '').trim());

const numericValue = (value) => {
    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
};

const contentIds = (value) => String(value ?? '')
    .split(',')
    .map((id) => id.trim())
    .filter((id) => /^\d+$/.test(id));

export const createMetaPixelTracker = ({ windowRef, documentRef, readPreferences }) => {
    const state = {
        pageViewUrl: null,
        pixelId: null,
        consentGranted: false,
        purchaseEvents: new Set(),
    };

    const hasMarketingConsent = () => {
        try {
            return readPreferences()?.marketing === true;
        } catch {
            return false;
        }
    };

    const ensurePixel = (pixelId) => {
        if (typeof windowRef.fbq !== 'function') {
            const fbq = function () {
                if (fbq.callMethod) {
                    fbq.callMethod.apply(fbq, arguments);
                } else {
                    fbq.queue.push(arguments);
                }
            };

            fbq.push = fbq;
            fbq.loaded = true;
            fbq.version = '2.0';
            fbq.queue = [];
            windowRef.fbq = fbq;
            windowRef._fbq = fbq;
        }

        const initializedPixelIds = windowRef.__rochaMetaPixelIds ?? new Set();
        windowRef.__rochaMetaPixelIds = initializedPixelIds;

        if (!initializedPixelIds.has(pixelId)) {
            windowRef.fbq('init', pixelId);
            initializedPixelIds.add(pixelId);
        }

        state.pixelId = pixelId;

        if (!state.consentGranted) {
            windowRef.fbq('consent', 'grant');
            state.consentGranted = true;
        }

        const existingPixelScript = documentRef.querySelector(
            'script[data-rocha-meta-pixel], script[src*="connect.facebook.net/en_US/fbevents.js"]',
        );

        if (!existingPixelScript) {
            const script = documentRef.createElement('script');
            script.async = true;
            script.src = 'https://connect.facebook.net/en_US/fbevents.js';
            script.dataset.rochaMetaPixel = 'true';
            documentRef.head.appendChild(script);
        }
    };

    const send = (eventName, parameters = {}, eventId = null) => {
        const pixelId = documentRef.body?.dataset.metaPixelId?.trim();

        if (!isValidPixelId(pixelId) || !hasMarketingConsent()) {
            return false;
        }

        ensurePixel(pixelId);

        if (eventId) {
            windowRef.fbq('track', eventName, parameters, { eventID: eventId });
        } else {
            windowRef.fbq('track', eventName, parameters);
        }

        return true;
    };

    const readStorage = (key) => {
        try {
            return windowRef.localStorage.getItem(key) === '1';
        } catch {
            return state.purchaseEvents.has(key);
        }
    };

    const markStorage = (key) => {
        state.purchaseEvents.add(key);

        try {
            windowRef.localStorage.setItem(key, '1');
        } catch {
            // Keep the in-memory guard when browser storage is unavailable.
        }
    };

    const initialize = () => {
        const pixelId = documentRef.body?.dataset.metaPixelId?.trim();

        if (!isValidPixelId(pixelId) || !hasMarketingConsent()) {
            if (typeof windowRef.fbq === 'function' && state.consentGranted) {
                windowRef.fbq('consent', 'revoke');
                state.consentGranted = false;
                state.pageViewUrl = null;
            }

            return;
        }

        ensurePixel(pixelId);

        if (state.pageViewUrl !== windowRef.location.href && send('PageView')) {
            state.pageViewUrl = windowRef.location.href;
        }

        const viewContent = documentRef.querySelector('[data-meta-view-content]');
        if (viewContent && viewContent.dataset.metaFired !== 'true') {
            const id = String(viewContent.dataset.contentId ?? '').trim();
            const value = numericValue(viewContent.dataset.value);

            if (id && send('ViewContent', {
                content_ids: [id],
                content_type: 'product',
                content_name: viewContent.dataset.contentName,
                currency: 'BRL',
                ...(value !== null ? { value } : {}),
            })) {
                viewContent.dataset.metaFired = 'true';
            }
        }

        const checkout = documentRef.querySelector('[data-meta-initiate-checkout]');
        if (checkout && checkout.dataset.metaFired !== 'true') {
            const value = numericValue(checkout.dataset.value);
            const numItems = numericValue(checkout.dataset.numItems);
            const ids = contentIds(checkout.dataset.contentIds);

            if (send('InitiateCheckout', {
                content_ids: ids,
                content_type: 'product',
                currency: 'BRL',
                ...(value !== null ? { value } : {}),
                ...(numItems !== null ? { num_items: numItems } : {}),
            })) {
                checkout.dataset.metaFired = 'true';
            }
        }

        const purchase = documentRef.querySelector('[data-meta-purchase]');
        if (purchase && purchase.dataset.metaFired !== 'true') {
            const orderId = String(purchase.dataset.orderId ?? '').trim();
            const value = numericValue(purchase.dataset.value);
            const key = `rocha-meta-purchase:${orderId}`;

            if (orderId && !readStorage(key) && value !== null && send('Purchase', {
                content_ids: contentIds(purchase.dataset.contentIds),
                content_type: 'product',
                currency: 'BRL',
                value,
                num_items: numericValue(purchase.dataset.numItems) ?? 0,
            }, purchase.dataset.eventId || `purchase-${orderId}`)) {
                markStorage(key);
                purchase.dataset.metaFired = 'true';
            }
        }
    };

    return { initialize, send };
};

export const bindMetaPixelEvents = (tracker, { windowRef, documentRef }) => {
    windowRef.addEventListener('rocha-meta-add-to-cart', (event) => {
        const detail = event.detail ?? {};
        const contentId = String(detail.contentId ?? '').trim();
        const quantity = numericValue(detail.quantity) ?? 1;
        const value = numericValue(detail.value);

        if (!contentId) {
            return;
        }

        tracker.send('AddToCart', {
            content_ids: [contentId],
            content_type: 'product',
            currency: 'BRL',
            quantity,
            ...(value !== null ? { value } : {}),
        });
    });

    windowRef.addEventListener('rocha:cookies-updated', tracker.initialize);
    documentRef.addEventListener('DOMContentLoaded', tracker.initialize);
    documentRef.addEventListener('livewire:navigated', tracker.initialize);
};
