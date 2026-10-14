import assert from 'node:assert/strict';
import test from 'node:test';
import { bindMetaPixelEvents, createMetaPixelTracker } from '../../resources/js/meta-pixel.js';

const PIXEL_ID = '2203469027275142';

const createEnvironment = ({
    marketing = false,
    pixelId = PIXEL_ID,
    nodes = {},
    storage = new Map(),
} = {}) => {
    const windowListeners = new Map();
    const documentListeners = new Map();
    const scripts = [];
    const preferences = { marketing };
    const elements = new Map(Object.entries(nodes));

    const windowRef = {
        location: { href: 'https://rochasports.com.br/produto/creatina' },
        localStorage: {
            getItem: (key) => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
        },
        addEventListener: (name, listener) => {
            const listeners = windowListeners.get(name) ?? [];
            listeners.push(listener);
            windowListeners.set(name, listeners);
        },
    };

    const documentRef = {
        body: { dataset: { metaPixelId: pixelId } },
        head: { appendChild: (script) => scripts.push(script) },
        createElement: () => ({ dataset: {} }),
        querySelector: (selector) => {
            if (selector.startsWith('script[')) {
                return scripts.find((script) => script.dataset.rochaMetaPixel === 'true') ?? null;
            }

            return elements.get(selector) ?? null;
        },
        addEventListener: (name, listener) => {
            const listeners = documentListeners.get(name) ?? [];
            listeners.push(listener);
            documentListeners.set(name, listeners);
        },
    };

    const tracker = createMetaPixelTracker({
        windowRef,
        documentRef,
        readPreferences: () => preferences,
    });
    bindMetaPixelEvents(tracker, { windowRef, documentRef });

    return {
        documentListeners,
        elements,
        preferences,
        scripts,
        storage,
        tracker,
        windowListeners,
        windowRef,
        runDocumentEvent: (name) => documentListeners.get(name)?.forEach((listener) => listener()),
        runWindowEvent: (name, detail = {}) => windowListeners.get(name)?.forEach((listener) => listener({ detail })),
        queuedCalls: () => (windowRef.fbq?.queue ?? []).map((args) => Array.from(args)),
    };
};

test('pixel does not load or send events before marketing consent', () => {
    const environment = createEnvironment({ marketing: false });

    environment.runDocumentEvent('DOMContentLoaded');

    assert.equal(environment.scripts.length, 0);
    assert.deepEqual(environment.queuedCalls(), []);

    environment.preferences.marketing = true;
    environment.runWindowEvent('rocha:cookies-updated');

    assert.equal(environment.scripts.length, 1);
    assert.ok(environment.queuedCalls().some(([action, event]) => action === 'track' && event === 'PageView'));
});

test('pixel tracks product, checkout, cart, and confirmed purchase with BRL values once', () => {
    const environment = createEnvironment({
        marketing: true,
        nodes: {
            '[data-meta-view-content]': {
                dataset: { contentId: '42', contentName: 'Creatina', value: '89.90', currency: 'BRL' },
            },
            '[data-meta-initiate-checkout]': {
                dataset: { value: '179.80', numItems: '2', contentIds: '42,77', currency: 'BRL' },
            },
            '[data-meta-purchase]': {
                dataset: { orderId: '123', eventId: 'purchase-123', value: '189.70', numItems: '2', contentIds: '42', currency: 'BRL' },
            },
        },
    });

    environment.runDocumentEvent('DOMContentLoaded');
    environment.runWindowEvent('rocha-meta-add-to-cart', { contentId: '42', value: 89.9, quantity: 1 });
    environment.tracker.initialize();

    const calls = environment.queuedCalls();
    const tracked = calls.filter(([action]) => action === 'track').map(([, event, parameters]) => [event, parameters]);

    assert.deepEqual(tracked.map(([event]) => event), ['PageView', 'ViewContent', 'InitiateCheckout', 'Purchase', 'AddToCart']);
    assert.deepEqual(tracked.find(([event]) => event === 'ViewContent')[1].content_ids, ['42']);
    assert.equal(tracked.find(([event]) => event === 'ViewContent')[1].currency, 'BRL');
    assert.equal(tracked.find(([event]) => event === 'InitiateCheckout')[1].value, 179.8);
    assert.deepEqual(tracked.find(([event]) => event === 'InitiateCheckout')[1].content_ids, ['42', '77']);
    assert.equal(tracked.find(([event]) => event === 'Purchase')[1].value, 189.7);
    assert.equal(tracked.find(([event]) => event === 'AddToCart')[1].value, 89.9);
    assert.equal(environment.storage.get('rocha-meta-purchase:123'), '1');
    assert.equal(calls.filter(([action, event]) => action === 'init' && event === PIXEL_ID).length, 1);
});

test('purchase is not emitted without a paid-order marker and repeats are suppressed across page loads', () => {
    const storage = new Map();
    const pending = createEnvironment({ marketing: true, storage });
    pending.runDocumentEvent('DOMContentLoaded');

    assert.equal(pending.queuedCalls().some(([action, event]) => action === 'track' && event === 'Purchase'), false);

    const confirmed = () => createEnvironment({
        marketing: true,
        storage,
        nodes: {
            '[data-meta-purchase]': {
                dataset: { orderId: '456', value: '50.00', numItems: '1', contentIds: '42' },
            },
        },
    });

    const firstVisit = confirmed();
    firstVisit.runDocumentEvent('DOMContentLoaded');
    assert.equal(firstVisit.queuedCalls().filter(([action, event]) => action === 'track' && event === 'Purchase').length, 1);

    const secondVisit = confirmed();
    secondVisit.runDocumentEvent('DOMContentLoaded');
    assert.equal(secondVisit.queuedCalls().some(([action, event]) => action === 'track' && event === 'Purchase'), false);
});

test('revoking marketing consent blocks later events', () => {
    const environment = createEnvironment({ marketing: true });
    environment.runDocumentEvent('DOMContentLoaded');
    environment.preferences.marketing = false;
    environment.runWindowEvent('rocha:cookies-updated');
    environment.runWindowEvent('rocha-meta-add-to-cart', { contentId: '42', value: 89.9, quantity: 1 });

    const calls = environment.queuedCalls();
    assert.ok(calls.some(([action, decision]) => action === 'consent' && decision === 'revoke'));
    assert.equal(calls.some(([action, event]) => action === 'track' && event === 'AddToCart'), false);
});
