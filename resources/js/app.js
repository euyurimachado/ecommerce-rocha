const STORAGE_KEY = 'rocha_cookie_preferences';

const defaultPreferences = {
    essential: true,
    analytics: false,
    marketing: false,
    updatedAt: null,
    version: 1,
};

const readPreferences = () => {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return stored ? { ...defaultPreferences, ...JSON.parse(stored) } : null;
    } catch {
        return null;
    }
};

const persistPreferences = (preferences) => {
    const payload = {
        ...defaultPreferences,
        ...preferences,
        essential: true,
        updatedAt: new Date().toISOString(),
    };

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    window.dispatchEvent(new CustomEvent('rocha:cookies-updated', { detail: payload }));

    return payload;
};

const initializeCookieConsent = () => {
    const banner = document.querySelector('[data-cookie-consent]');
    const modal = document.querySelector('[data-cookie-modal]');
    const analyticsInputs = document.querySelectorAll('[data-cookie-category="analytics"]');
    const marketingInputs = document.querySelectorAll('[data-cookie-category="marketing"]');

    if (!banner || !modal) {
        return;
    }

    if (banner.dataset.cookieConsentReady === 'true') {
        return;
    }

    banner.dataset.cookieConsentReady = 'true';

    const setInputs = (preferences = defaultPreferences) => {
        analyticsInputs.forEach((input) => {
            input.checked = Boolean(preferences.analytics);
        });

        marketingInputs.forEach((input) => {
            input.checked = Boolean(preferences.marketing);
        });
    };

    const openModal = () => {
        setInputs(readPreferences() ?? defaultPreferences);
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    };

    const hideBanner = () => {
        banner.classList.add('hidden');
    };

    const savePreferences = (preferences) => {
        const saved = persistPreferences(preferences);
        setInputs(saved);
        hideBanner();
        closeModal();
    };

    const storedPreferences = readPreferences();

    if (storedPreferences) {
        setInputs(storedPreferences);
        hideBanner();
    } else {
        banner.classList.remove('hidden');
    }

    document.querySelectorAll('[data-cookie-preferences-open]').forEach((button) => {
        button.addEventListener('click', openModal);
    });

    document.querySelectorAll('[data-cookie-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.querySelectorAll('[data-cookie-accept-all]').forEach((button) => {
        button.addEventListener('click', () => savePreferences({ analytics: true, marketing: true }));
    });

    document.querySelectorAll('[data-cookie-reject]').forEach((button) => {
        button.addEventListener('click', () => savePreferences({ analytics: false, marketing: false }));
    });

    document.querySelectorAll('[data-cookie-save]').forEach((button) => {
        button.addEventListener('click', () => {
            savePreferences({
                analytics: [...analyticsInputs].some((input) => input.checked),
                marketing: [...marketingInputs].some((input) => input.checked),
            });
        });
    });
};

document.addEventListener('DOMContentLoaded', initializeCookieConsent);
document.addEventListener('livewire:navigated', initializeCookieConsent);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // A loja continua funcionando mesmo quando o navegador bloqueia PWA.
        });
    });
}
