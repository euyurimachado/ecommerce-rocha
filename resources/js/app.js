const STORAGE_KEY = 'rocha_cookie_preferences';

const ignoredSentenceCaseElements = new Set(['SCRIPT', 'STYLE', 'CODE', 'PRE', 'TEXTAREA']);

const toPortugueseSentenceCase = (value) => {
    const trimmed = value.trim();

    if (!trimmed || !/\p{L}/u.test(trimmed) || trimmed !== trimmed.toLocaleUpperCase('pt-BR')) {
        return value;
    }

    const normalized = value.toLocaleLowerCase('pt-BR');

    return normalized.replace(/(^|[.!?]\s+)(\p{L})/gu, (_, prefix, letter) => `${prefix}${letter.toLocaleUpperCase('pt-BR')}`);
};

const normalizeStorefrontText = (root = document.body) => {
    if (!root) {
        return;
    }

    if (root.nodeType === Node.TEXT_NODE) {
        if (!ignoredSentenceCaseElements.has(root.parentElement?.tagName)) {
            const normalized = toPortugueseSentenceCase(root.nodeValue ?? '');

            if (normalized !== root.nodeValue) {
                root.nodeValue = normalized;
            }
        }

        return;
    }

    if (!(root instanceof Element) || ignoredSentenceCaseElements.has(root.tagName)) {
        return;
    }

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let textNode = walker.nextNode();

    while (textNode) {
        if (!ignoredSentenceCaseElements.has(textNode.parentElement?.tagName)) {
            const normalized = toPortugueseSentenceCase(textNode.nodeValue ?? '');

            if (normalized !== textNode.nodeValue) {
                textNode.nodeValue = normalized;
            }
        }

        textNode = walker.nextNode();
    }

    root.querySelectorAll('[placeholder]').forEach((element) => {
        element.placeholder = toPortugueseSentenceCase(element.placeholder);
    });
};

const initializeSentenceCase = () => {
    normalizeStorefrontText(document.body);

    if (document.body.dataset.sentenceCaseReady === 'true') {
        return;
    }

    document.body.dataset.sentenceCaseReady = 'true';

    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'characterData') {
                normalizeStorefrontText(mutation.target);

                return;
            }

            mutation.addedNodes.forEach(normalizeStorefrontText);
        });
    }).observe(document.body, { childList: true, characterData: true, subtree: true });
};

const initializeInfiniteProductScroll = () => {
    document.querySelectorAll('[data-infinite-scroll]').forEach((sentinel) => {
        if (sentinel.dataset.infiniteReady === 'true' || !sentinel.dataset.nextUrl) {
            return;
        }

        sentinel.dataset.infiniteReady = 'true';
        let loading = false;

        const observer = new IntersectionObserver(async (entries) => {
            if (!entries.some((entry) => entry.isIntersecting) || loading || !sentinel.dataset.nextUrl) {
                return;
            }

            loading = true;
            observer.unobserve(sentinel);
            sentinel.innerHTML = '<span>Carregando mais produtos...</span>';

            try {
                const response = await fetch(sentinel.dataset.nextUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error(`Falha ao carregar produtos: ${response.status}`);
                }

                const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                const currentGrid = document.querySelector('[data-infinite-product-grid]');
                const nextGrid = nextDocument.querySelector('[data-infinite-product-grid]');
                const nextSentinel = nextDocument.querySelector('[data-infinite-scroll]');

                if (!currentGrid || !nextGrid) {
                    throw new Error('A próxima página não contém a grade de produtos.');
                }

                [...nextGrid.children].forEach((productCard) => currentGrid.append(productCard));
                sentinel.dataset.nextUrl = nextSentinel?.dataset.nextUrl ?? '';

                if (sentinel.dataset.nextUrl) {
                    sentinel.innerHTML = '<span>Carregando mais produtos...</span>';
                    observer.observe(sentinel);
                } else {
                    sentinel.innerHTML = '<span>Todos os produtos foram carregados.</span>';
                }
            } catch {
                sentinel.innerHTML = '<button class="font-bold text-rocha-blue" type="button">Tentar carregar novamente</button>';
                sentinel.querySelector('button')?.addEventListener('click', () => observer.observe(sentinel), { once: true });
            } finally {
                loading = false;
            }
        }, { rootMargin: '300px 0px' });

        observer.observe(sentinel);
    });
};

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
    const mainImages = document.querySelectorAll('[data-product-main-image]');
    const gallery = document.querySelector('[data-product-gallery]');
    const mobileGallery = document.querySelector('[data-product-mobile-gallery]');
    const mobileSlides = [...document.querySelectorAll('[data-product-mobile-slide]')];
    const mobileDots = [...document.querySelectorAll('[data-product-mobile-dot]')];

    const setActiveMobileDot = (activeIndex) => {
        mobileDots.forEach((dot, index) => {
            const isActive = index === activeIndex;
            dot.classList.toggle('w-5', isActive);
            dot.classList.toggle('bg-rocha-blue', isActive);
            dot.classList.toggle('w-1.5', !isActive);
            dot.classList.toggle('bg-slate-300', !isActive);
        });
    };

    if (mobileGallery && mobileSlides.length > 1) {
        let scrollFrame = null;

        mobileGallery.addEventListener('scroll', () => {
            window.cancelAnimationFrame(scrollFrame);
            scrollFrame = window.requestAnimationFrame(() => {
                const activeIndex = Math.round(mobileGallery.scrollLeft / mobileGallery.clientWidth);
                setActiveMobileDot(Math.min(mobileSlides.length - 1, Math.max(0, activeIndex)));
            });
        }, { passive: true });
    }

    const setActiveGalleryImage = (imageUrl) => {
        if (!imageUrl) {
            return;
        }

        mainImages.forEach((mainImage) => {
            mainImage.src = imageUrl;
        });

        const mobileSlideIndex = mobileSlides.findIndex((slide) => slide.dataset.productMobileSlide === imageUrl);

        if (mobileGallery && mobileSlideIndex >= 0) {
            mobileGallery.scrollTo({ left: mobileSlideIndex * mobileGallery.clientWidth, behavior: 'smooth' });
            setActiveMobileDot(mobileSlideIndex);
        }

        gallery?.querySelectorAll('[data-product-gallery-thumb]').forEach((thumb) => {
            const isActive = thumb.dataset.productGalleryThumb === imageUrl;

            thumb.classList.toggle('border-rocha-blue', isActive);
            thumb.classList.toggle('ring-2', isActive);
            thumb.classList.toggle('ring-rocha-blue/20', isActive);
            thumb.classList.toggle('border-slate-200', !isActive);
        });
    };

    gallery?.querySelectorAll('[data-product-gallery-thumb]').forEach((button) => {
        if (button.dataset.galleryReady === 'true') {
            return;
        }

        button.dataset.galleryReady = 'true';

        button.addEventListener('click', () => {
            setActiveGalleryImage(button.dataset.productGalleryThumb);
        });
    });

    const variationsContainer = document.querySelector('[data-product-variations]');
    const variationButtons = document.querySelectorAll('[data-product-variation-option]');
    const productPrices = document.querySelectorAll('[data-product-price]');
    const productComparePrices = document.querySelectorAll('[data-product-compare-price]');
    const productAvailabilities = document.querySelectorAll('[data-product-availability]');
    const productDiscount = document.querySelector('[data-product-discount]');
    const productStock = document.querySelector('[data-product-stock]');
    const addToCartButtons = document.querySelectorAll('[data-add-to-cart-button]');
    window.rochaProductVariantSelections = {};
    let purchaseBarSyncTimer = null;

    const syncPurchaseBarSelections = () => {
        window.clearTimeout(purchaseBarSyncTimer);
        purchaseBarSyncTimer = window.setTimeout(() => {
            const purchaseBar = document.querySelector('[data-purchase-bar]');
            const livewireRoot = purchaseBar?.closest('[wire\\:id]');
            const componentId = livewireRoot?.getAttribute('wire:id');

            if (componentId && window.Livewire) {
                window.Livewire.find(componentId)?.call('selectVariants', { ...window.rochaProductVariantSelections });
            }
        }, 0);
    };

    const selectedVariationButtons = () => Array.from(variationButtons).filter((option) => option.getAttribute('aria-pressed') === 'true');

    const updateProductVariantState = () => {
        const activeButtons = selectedVariationButtons();
        const priceSource = activeButtons.find((button) => button.dataset.variationHasPrice === 'true');
        const comparePriceSource = activeButtons.find((button) => button.dataset.variationHasComparePrice === 'true');
        const stockSource = activeButtons.find((button) => button.dataset.variationControlsStock === 'true');
        const available = Number.parseInt((stockSource?.dataset.variationAvailable ?? variationsContainer?.dataset.baseAvailable ?? '0'), 10);

        const displayedPrice = priceSource?.dataset.variationPrice || variationsContainer?.dataset.basePrice || '';
        const priceCents = Number.parseInt(priceSource?.dataset.variationPriceCents || variationsContainer?.dataset.basePriceCents || '0', 10);
        const comparePrice = comparePriceSource?.dataset.variationComparePrice || variationsContainer?.dataset.baseComparePrice || '';
        const comparePriceCents = Number.parseInt(comparePriceSource?.dataset.variationComparePriceCents || variationsContainer?.dataset.baseComparePriceCents || '0', 10);

        productPrices.forEach((element) => {
            if (displayedPrice) element.textContent = displayedPrice;
        });
        productComparePrices.forEach((element) => {
            element.textContent = comparePrice;
            element.classList.toggle('hidden', !comparePrice || comparePriceCents <= priceCents);
        });

        if (productDiscount) {
            const percentage = comparePriceCents > priceCents && comparePriceCents > 0 ? Math.round((1 - priceCents / comparePriceCents) * 100) : 0;
            productDiscount.textContent = percentage ? `-${percentage}%` : '';
            productDiscount.classList.toggle('hidden', !percentage);
        }

        productAvailabilities.forEach((productAvailability) => {
            const isAvailable = available > 0;

            productAvailability.textContent = isAvailable ? 'Disponível para entrega local ou retirada' : 'Produto indisponível no momento';
            productAvailability.classList.toggle('text-emerald-700', isAvailable);
            productAvailability.classList.toggle('text-rose-700', !isAvailable);
        });

        if (productStock && !Number.isNaN(available)) {
            productStock.textContent = `${available} un.`;
        }

        addToCartButtons.forEach((button) => {
            button.disabled = !Number.isNaN(available) && available <= 0;
        });

        syncPurchaseBarSelections();
    };

    const selectVariation = (button, updateImage = true) => {
        const variationName = button.dataset.variationName;
        const variationValue = button.dataset.variationValue;

        if (!variationName || !variationValue) {
            return;
        }

        window.rochaProductVariantSelections[variationName] = variationValue;

        if (updateImage && button.dataset.variationImage) {
            setActiveGalleryImage(button.dataset.variationImage);
        }

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
            option.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });

        updateProductVariantState();
    };

    variationButtons.forEach((button) => {
        if (button.getAttribute('aria-pressed') === 'true') {
            selectVariation(button, false);
        }

        if (button.dataset.variationReady === 'true') {
            return;
        }

        button.dataset.variationReady = 'true';
        button.addEventListener('click', () => selectVariation(button, true));
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

const initializePixCopy = () => {
    document.querySelectorAll('[data-copy-pix]').forEach((button) => {
        if (button.dataset.copyReady === 'true') return;
        button.dataset.copyReady = 'true';
        button.addEventListener('click', async () => {
            const code = document.querySelector('#pix-code')?.value;
            if (!code) return;
            await navigator.clipboard.writeText(code);
            button.textContent = 'Código copiado!';
            window.setTimeout(() => { button.textContent = 'Copiar código PIX'; }, 1800);
        });
    });
};

document.addEventListener('DOMContentLoaded', initializeCookieConsent);
document.addEventListener('DOMContentLoaded', initializeSentenceCase);
document.addEventListener('DOMContentLoaded', initializeInfiniteProductScroll);
document.addEventListener('DOMContentLoaded', initializeHomeHeroSlider);
document.addEventListener('DOMContentLoaded', initializeProductPage);
document.addEventListener('DOMContentLoaded', initializeCheckoutFields);
document.addEventListener('DOMContentLoaded', initializePixCopy);
document.addEventListener('livewire:navigated', initializeCookieConsent);
document.addEventListener('livewire:navigated', initializeSentenceCase);
document.addEventListener('livewire:navigated', initializeInfiniteProductScroll);
document.addEventListener('livewire:navigated', initializeHomeHeroSlider);
document.addEventListener('livewire:navigated', initializeProductPage);
document.addEventListener('livewire:navigated', initializeCheckoutFields);
document.addEventListener('livewire:navigated', initializePixCopy);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // A loja continua funcionando mesmo quando o navegador bloqueia PWA.
        });
    });
}
