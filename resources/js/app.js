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

const initializeHomeHeroSlider = () => {
    document.querySelectorAll('[data-home-hero-slider]').forEach((slider) => {
        if (slider.dataset.sliderReady === 'true') {
            return;
        }

        slider.dataset.sliderReady = 'true';

        const slides = [...slider.querySelectorAll('[data-home-hero-slide]')];
        const dots = [...slider.querySelectorAll('[data-home-hero-dot]')];
        const previousButton = slider.querySelector('[data-home-hero-prev]');
        const nextButton = slider.querySelector('[data-home-hero-next]');
        let activeIndex = 0;
        let intervalId = null;
        let touchStartX = 0;
        let touchStartY = 0;
        let touchDeltaX = 0;
        let isTouching = false;

        if (slides.length <= 1) {
            return;
        }

        const setActiveSlide = (nextIndex) => {
            activeIndex = (nextIndex + slides.length) % slides.length;

            slides.forEach((slide, index) => {
                const isActive = index === activeIndex;

                slide.classList.toggle('opacity-100', isActive);
                slide.classList.toggle('opacity-0', !isActive);
                slide.classList.toggle('pointer-events-none', !isActive);
            });

            dots.forEach((dot, index) => {
                const isActive = index === activeIndex;

                dot.classList.toggle('w-7', isActive);
                dot.classList.toggle('w-2.5', !isActive);
                dot.classList.toggle('bg-white', isActive);
                dot.classList.toggle('bg-white/45', !isActive);
                dot.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        };

        const startAutoplay = () => {
            window.clearInterval(intervalId);
            intervalId = window.setInterval(() => setActiveSlide(activeIndex + 1), 5500);
        };

        previousButton?.addEventListener('click', () => {
            setActiveSlide(activeIndex - 1);
            startAutoplay();
        });

        nextButton?.addEventListener('click', () => {
            setActiveSlide(activeIndex + 1);
            startAutoplay();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                setActiveSlide(index);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', () => window.clearInterval(intervalId));
        slider.addEventListener('mouseleave', startAutoplay);
        slider.addEventListener('focusin', () => window.clearInterval(intervalId));
        slider.addEventListener('focusout', startAutoplay);
        slider.addEventListener(
            'touchstart',
            (event) => {
                const touch = event.touches[0];

                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
                touchDeltaX = 0;
                isTouching = true;
                window.clearInterval(intervalId);
            },
            { passive: true },
        );
        slider.addEventListener(
            'touchmove',
            (event) => {
                if (!isTouching) {
                    return;
                }

                const touch = event.touches[0];
                const deltaX = touch.clientX - touchStartX;
                const deltaY = touch.clientY - touchStartY;

                if (Math.abs(deltaY) > Math.abs(deltaX)) {
                    return;
                }

                touchDeltaX = deltaX;
            },
            { passive: true },
        );
        slider.addEventListener('touchend', () => {
            if (!isTouching) {
                return;
            }

            const minimumSwipeDistance = Math.min(90, slider.offsetWidth * 0.18);

            if (Math.abs(touchDeltaX) >= minimumSwipeDistance) {
                setActiveSlide(activeIndex + (touchDeltaX < 0 ? 1 : -1));
            }

            isTouching = false;
            touchDeltaX = 0;
            startAutoplay();
        });
        slider.addEventListener('touchcancel', () => {
            isTouching = false;
            touchDeltaX = 0;
            startAutoplay();
        });

        setActiveSlide(0);
        startAutoplay();
    });
};

const initializeProductPage = () => {
    const mainImage = document.querySelector('[data-product-main-image]');
    const gallery = document.querySelector('[data-product-gallery]');

    gallery?.querySelectorAll('[data-product-gallery-thumb]').forEach((button) => {
        if (button.dataset.galleryReady === 'true') {
            return;
        }

        button.dataset.galleryReady = 'true';

        button.addEventListener('click', () => {
            if (!mainImage) {
                return;
            }

            mainImage.src = button.dataset.productGalleryThumb;

            gallery.querySelectorAll('[data-product-gallery-thumb]').forEach((thumb) => {
                const isActive = thumb === button;

                thumb.classList.toggle('border-rocha-blue', isActive);
                thumb.classList.toggle('ring-2', isActive);
                thumb.classList.toggle('ring-rocha-blue/20', isActive);
                thumb.classList.toggle('border-slate-200', !isActive);
            });
        });
    });

    const variationButtons = document.querySelectorAll('[data-product-variation-option]');
    window.rochaProductVariantSelections = {};

    const selectVariation = (button) => {
        const variationName = button.dataset.variationName;
        const variationValue = button.dataset.variationValue;

        if (!variationName || !variationValue) {
            return;
        }

        window.rochaProductVariantSelections[variationName] = variationValue;

        variationButtons.forEach((option) => {
            if (option.dataset.variationName !== variationName) {
                return;
            }

            const isActive = option === button;

            option.classList.toggle('border-rocha-blue', isActive);
            option.classList.toggle('bg-rocha-blue/5', isActive);
            option.classList.toggle('text-rocha-blue', isActive);
            option.classList.toggle('border-slate-200', !isActive);
            option.classList.toggle('bg-white', !isActive);
            option.classList.toggle('text-slate-600', !isActive);
            option.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    variationButtons.forEach((button) => {
        if (button.getAttribute('aria-pressed') === 'true') {
            selectVariation(button);
        }

        if (button.dataset.variationReady === 'true') {
            return;
        }

        button.dataset.variationReady = 'true';
        button.addEventListener('click', () => selectVariation(button));
    });

    document.querySelectorAll('[data-share-product]').forEach((button) => {
        if (button.dataset.shareReady === 'true') {
            return;
        }

        button.dataset.shareReady = 'true';

        button.addEventListener('click', async () => {
            const shareData = {
                title: document.title,
                url: window.location.href,
            };

            if (navigator.share) {
                await navigator.share(shareData).catch(() => {});

                return;
            }

            await navigator.clipboard?.writeText(window.location.href).catch(() => {});
            button.classList.add('text-rocha-blue');
            window.setTimeout(() => button.classList.remove('text-rocha-blue'), 1200);
        });
    });
};

const dispatchMaskedInput = (input) => {
    input.dataset.masking = 'true';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    delete input.dataset.masking;
};

const maskPhone = (value) => {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 6) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    }

    if (digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }

    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
};

const maskPostalCode = (value) => {
    const digits = value.replace(/\D/g, '').slice(0, 8);

    return digits.length > 5 ? `${digits.slice(0, 5)}-${digits.slice(5)}` : digits;
};

const initializeCheckoutFields = () => {
    document.querySelectorAll('[data-phone-mask]').forEach((input) => {
        if (input.dataset.phoneMaskReady === 'true') {
            return;
        }

        input.dataset.phoneMaskReady = 'true';

        input.addEventListener('input', () => {
            if (input.dataset.masking === 'true') {
                return;
            }

            const masked = maskPhone(input.value);

            if (input.value !== masked) {
                input.value = masked;
                dispatchMaskedInput(input);
            }
        });
    });

    document.querySelectorAll('[data-cep-mask]').forEach((input) => {
        if (input.dataset.cepMaskReady === 'true') {
            return;
        }

        input.dataset.cepMaskReady = 'true';

        input.addEventListener('input', () => {
            if (input.dataset.masking === 'true') {
                return;
            }

            const masked = maskPostalCode(input.value);

            if (input.value !== masked) {
                input.value = masked;
                dispatchMaskedInput(input);
            }
        });
    });

    document.querySelectorAll('[data-email-normalize]').forEach((input) => {
        if (input.dataset.emailNormalizeReady === 'true') {
            return;
        }

        input.dataset.emailNormalizeReady = 'true';

        input.addEventListener('blur', () => {
            const normalized = input.value.trim().toLowerCase();

            if (input.value !== normalized) {
                input.value = normalized;
                dispatchMaskedInput(input);
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', initializeCookieConsent);
document.addEventListener('DOMContentLoaded', initializeHomeHeroSlider);
document.addEventListener('DOMContentLoaded', initializeProductPage);
document.addEventListener('DOMContentLoaded', initializeCheckoutFields);
document.addEventListener('livewire:navigated', initializeCookieConsent);
document.addEventListener('livewire:navigated', initializeHomeHeroSlider);
document.addEventListener('livewire:navigated', initializeProductPage);
document.addEventListener('livewire:navigated', initializeCheckoutFields);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // A loja continua funcionando mesmo quando o navegador bloqueia PWA.
        });
    });
}
