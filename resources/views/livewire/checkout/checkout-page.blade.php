<div>
    @if ($items->isEmpty())
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            <p class="font-semibold text-slate-950">Seu carrinho ainda está vazio.</p>
            <p class="mt-2">Adicione produtos antes de finalizar a compra.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white">Continuar comprando</a>
        </div>
    @else
        <form wire:submit="placeOrder" class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-4">
                @if ($checkoutError)
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">
                        {{ $checkoutError }}
                    </div>
                @endif

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold md:text-xl">1. Identificação</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Nome completo</span>
                            <input wire:model="customer_name" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="name">
                            @error('customer_name') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Telefone / WhatsApp</span>
                            <input wire:model="customer_phone" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="tel" autocomplete="tel" inputmode="tel" maxlength="15" placeholder="(22) 99999-0000" data-phone-mask>
                            @error('customer_phone') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                        </label>
                        @if ($fulfillment_method === 'delivery' && app(\App\Support\Shipping\ShippingProviderManager::class)->provider() === 'melhor_envio')
                            <label class="block">
                                <span class="text-sm font-bold text-slate-700">CPF ou CNPJ do destinatário</span>
                                <input wire:model="customer_tax_id" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" inputmode="numeric" maxlength="18" autocomplete="off" placeholder="Somente números">
                                @error('customer_tax_id') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                        @endif
                        <label class="block md:col-span-2">
                            <span class="text-sm font-bold text-slate-700">E-mail</span>
                            <input wire:model="customer_email" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="email" autocomplete="email" inputmode="email" placeholder="voce@email.com" data-email-normalize>
                            @error('customer_email') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold md:text-xl">2. Entrega ou retirada</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model.live="fulfillment_method" class="mt-1" type="radio" value="delivery">
                            <span>
                                <span class="block font-bold">Entrega local</span>
                                <span class="mt-1 block text-sm text-slate-600">Receba no endereço informado.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model.live="fulfillment_method" class="mt-1" type="radio" value="pickup">
                            <span>
                                <span class="block font-bold">Retirada na loja</span>
                                <span class="mt-1 block text-sm text-slate-600">Separaremos o pedido para retirada.</span>
                            </span>
                        </label>
                    </div>

                    @if ($fulfillment_method === 'delivery')
                        <div class="mt-5 grid gap-4 md:grid-cols-6">
                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-slate-700">CEP</span>
                                <input wire:model="postal_code" wire:blur="lookupPostalCode" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="postal-code" inputmode="numeric" maxlength="9" placeholder="28000-000" data-cep-mask>
                                <span wire:loading wire:target="lookupPostalCode" class="mt-1 block text-sm text-slate-500">Buscando endereço...</span>
                                @if ($addressLookupError)
                                    <span class="mt-1 block text-sm text-rose-700">{{ $addressLookupError }}</span>
                                @endif
                                @error('postal_code') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-6">
                                <span class="text-sm font-bold text-slate-700">Rua</span>
                                <input wire:model="street" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="address-line1">
                                @error('street') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-slate-700">Número</span>
                                <input wire:model="number" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text">
                                @error('number') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-4">
                                <span class="text-sm font-bold text-slate-700">Complemento</span>
                                <input wire:model="complement" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="address-line2">
                            </label>
                            <label class="block md:col-span-3">
                                <span class="text-sm font-bold text-slate-700">Bairro</span>
                                <input wire:model="neighborhood" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text">
                                @error('neighborhood') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-slate-700">Cidade</span>
                                <input wire:model="city" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text">
                                @error('city') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-1">
                                <span class="text-sm font-bold text-slate-700">UF</span>
                                <input wire:model="state" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" maxlength="2">
                                @error('state') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <button type="button" wire:click="loadShippingQuotes" class="mt-4 rounded-lg border border-rocha-blue px-4 py-2 text-sm font-bold text-rocha-blue">Calcular frete</button>
                        @if ($shipping_quotes)
                            <div class="mt-4 grid gap-2">
                                @foreach ($shipping_quotes as $quote)
                                    <label class="flex cursor-pointer items-center justify-between rounded-lg border border-slate-200 p-4">
                                        <span class="flex items-start gap-3">
                                            <input wire:model.live="selected_shipping" type="radio" value="{{ $quote['service_id'] }}">
                                            <span><strong class="block">{{ $quote['service_name'] }}</strong><small class="text-slate-500">{{ $quote['carrier'] }} · até {{ $quote['delivery_days'] }} dias úteis</small></span>
                                        </span>
                                        <strong>{{ $quote['price_cents'] === 0 ? 'Grátis' : 'R$ '.number_format($quote['price_cents'] / 100, 2, ',', '.') }}</strong>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="mt-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                            Retirada na {{ $storeSettings->name }}. A equipe confirmará o horário pelo WhatsApp após o pedido.
                        </div>
                    @endif
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold md:text-xl">3. Pagamento</h2>
                    @if (! $paymentCapabilities)
                        <p class="mt-5 text-sm text-rose-700" role="alert">Nenhum meio de pagamento online está configurado. Entre em contato com a loja.</p>
                    @else
                    <div class="mt-5 grid gap-3">
                        @if ($paymentCapabilities->pix)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                                <input wire:model.live="payment_method" class="mt-1" type="radio" value="pix">
                                <span><span class="block font-bold">PIX</span><span class="text-sm text-slate-600">QR Code e código copia e cola. O pedido só será pago após confirmação.</span></span>
                            </label>
                        @endif
                        @if ($paymentCapabilities->creditCard)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                                <input wire:model.live="payment_method" class="mt-1" type="radio" value="credit_card">
                                <span><span class="block font-bold">Cartão de crédito</span><span class="text-sm text-slate-600">{{ $paymentProvider === 'asaas' ? 'Pagamento no ambiente seguro do Asaas.' : 'Pagamento seguro com cartão.' }}</span></span>
                            </label>
                        @endif
                    </div>
                    @if ($payment_method === 'pix' && $paymentProvider === 'mercado_pago' && ! ($fulfillment_method === 'delivery' && app(\App\Support\Shipping\ShippingProviderManager::class)->provider() === 'melhor_envio'))
                        <label class="mt-4 block">
                            <span class="text-sm font-bold text-slate-700">CPF ou CNPJ para gerar o PIX</span>
                            <input wire:model="customer_tax_id" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" inputmode="numeric" maxlength="14" autocomplete="off" placeholder="Somente números">
                            @error('customer_tax_id') <span class="mt-1 block text-sm text-rose-700">Informe um CPF ou CNPJ válido para gerar o PIX.</span> @enderror
                        </label>
                    @endif
                    @if ($paymentProvider === 'mercado_pago')
                        <div wire:ignore id="paymentBrick_region" class="mt-4" hidden>
                            <p id="paymentBrick_loading" class="text-sm text-slate-600" role="status" aria-live="polite">Carregando pagamento seguro...</p>
                            <div id="paymentBrick_skeleton" class="mt-3 animate-pulse space-y-3" aria-hidden="true"><div class="h-11 rounded bg-slate-100"></div><div class="grid grid-cols-2 gap-3"><div class="h-11 rounded bg-slate-100"></div><div class="h-11 rounded bg-slate-100"></div></div></div>
                            <div id="paymentBrick_error" class="mt-4 text-sm text-rose-700" hidden role="alert"><p>Não foi possível carregar o formulário de cartão. Tente novamente ou selecione PIX.</p><button id="paymentBrick_retry" type="button" class="mt-2 rounded border border-rose-300 px-3 py-2 font-semibold hover:bg-rose-50">Tentar novamente</button></div>
                            <div id="paymentBrick_mount_host" class="mt-4"></div>
                        </div>
                        @if ($payment_method === 'credit_card')
                            @error('card_token') <span class="mt-2 block text-sm text-rose-700">Não foi possível validar o cartão. Confira os dados e tente novamente.</span> @enderror
                        @endif
                    @endif
                    @endif
                    @error('payment_method') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror

                    <label class="mt-5 block">
                        <span class="text-sm font-bold text-slate-700">Observações</span>
                        <textarea wire:model="notes" class="mt-2 min-h-24 w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-rocha-blue" maxlength="500"></textarea>
                    </label>

                    <label class="mt-5 flex items-start gap-3 text-sm text-slate-600">
                        <input wire:model="privacy_accepted" class="mt-1" type="checkbox">
                        <span>Li e aceito a política de privacidade e o contato para atualizações deste pedido.</span>
                    </label>
                    @error('privacy_accepted') <span class="mt-2 block text-sm text-rose-700">{{ $message }}</span> @enderror
                </section>
            </div>

            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold md:text-xl">Resumo</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($items as $item)
                        @php($product = $item['product'])
                        <div class="flex gap-3 text-sm">
                            <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-md bg-slate-100">
                                <img class="h-full w-full object-contain" src="{{ $product->imageUrlForSelections($item['variant_selections']) }}" alt="{{ $product->name }}" loading="lazy">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-950">{{ $product->name }}</p>
                                <p class="mt-1 text-slate-500">{{ $item['quantity'] }} x R$ {{ number_format($item['unit_price_cents'] / 100, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 border-t border-slate-200 pt-5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="font-bold">{{ $subtotal }}</span>
                    </div>
                    <div class="mt-2 flex justify-between">
                        <span class="text-slate-600">Entrega</span>
                        <span class="font-bold">{{ $shipping }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $shippingEstimate }}</p>
                    @if ($coupon)
                        <div class="mt-2 flex justify-between text-emerald-700">
                            <span>Cupom {{ $coupon->code }}</span>
                            <span class="font-bold">- {{ $discount }}</span>
                        </div>
                    @endif
                    <div class="mt-4 flex justify-between text-lg">
                        <span class="font-bold">Total</span>
                        <span class="font-bold">{{ $total }}</span>
                    </div>
                </div>
                @if ($payment_method === 'credit_card' && $paymentProvider === 'mercado_pago')
                    <button id="paymentBrick_checkout_status" class="mt-6 flex w-full justify-center rounded-lg bg-slate-200 px-5 py-3 font-bold text-slate-700 disabled:cursor-not-allowed" type="button" disabled aria-live="polite">Carregando formulário do cartão...</button>
                @else
                    <button wire:loading.attr="disabled" @disabled(! $paymentProvider) wire:target="placeOrder" class="mt-6 flex w-full justify-center rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-70" type="submit">
                        <span wire:loading.remove wire:target="placeOrder">Finalizar pedido</span>
                        <span wire:loading wire:target="placeOrder">Finalizando...</span>
                    </button>
                @endif
            </aside>
        </form>
    @endif

    @if ($paymentProvider === 'mercado_pago')
        @if ($paymentPublicKey)
            @assets
                <link rel="preconnect" href="https://sdk.mercadopago.com" crossorigin>
                <script src="https://sdk.mercadopago.com/js/v2"></script>
            @endassets
        @endif
        @script
            console.info('[MP-CARD] script-start');
            const paymentPublicKey = @js($paymentPublicKey);
            const hasUsablePublicKey = typeof paymentPublicKey === 'string'
                && /^(TEST|APP_USR)-[A-Za-z0-9_-]+$/.test(paymentPublicKey.trim());
            const diagnostic = (event, details = {}) => console.info('[MP-CARD] ' + event, details);
            let brickController = null;
            let controllerMount = null;
            let pendingCreation = null;
            let mercadoPagoInstance = null;
            let brickGeneration = 0;
            let brickSubmitting = false;
            let mountSequence = 0;
            let cleanupQueue = Promise.resolve();
            let reconcilePromise = null;
            let reconcileGeneration = null;
            const root = $wire.$el;

            const isCardSelected = () => $wire.payment_method === 'credit_card';
            const getContainer = () => document.getElementById('paymentBrick_mount_host');
            const logCardState = (event) => {
                const container = getContainer();
                const rect = container?.getBoundingClientRect();
                diagnostic(event, {
                    paymentMethod: ['pix', 'credit_card', 'debit_card'].includes($wire.payment_method) ? $wire.payment_method : 'unexpected',
                    publicKeyPresent: hasUsablePublicKey,
                    sdkPresent: typeof window.MercadoPago === 'function',
                    containerPresent: Boolean(container),
                    containerVisible: Boolean(container?.isConnected && rect?.width > 0 && rect?.height > 0),
                    containerWidth: Math.round(rect?.width || 0),
                    containerHeight: Math.round(rect?.height || 0),
                });
            };
            const setCheckoutStatus = (ready, failed = false) => {
                const button = document.getElementById('paymentBrick_checkout_status');
                if (!button) return;
                button.hidden = ready;
                button.textContent = failed ? 'Cartão indisponível — tente novamente ou selecione PIX' : 'Carregando formulário do cartão...';
            };
            const getRegion = () => document.getElementById('paymentBrick_region');
            const setBrickState = (ready, failed = false) => {
                const loading = document.getElementById('paymentBrick_loading');
                const error = document.getElementById('paymentBrick_error');
                const skeleton = document.getElementById('paymentBrick_skeleton');

                if (loading) loading.hidden = ready || failed;
                if (skeleton) skeleton.hidden = ready || failed;
                if (error) error.hidden = !failed;
            };
            const reportBrickError = (error, stage) => {
                const type = ['Error', 'TypeError', 'ReferenceError', 'SyntaxError'].includes(error?.name) ? error.name : 'unknown';
                const rawCode = String(error?.code || '');
                const code = /^[A-Za-z0-9_.-]{1,48}$/.test(rawCode) ? rawCode : 'unavailable';
                console.error('[MP-CARD] on-error', { stage, type, code, message: 'Mercado Pago Brick operation failed' });
            };
            const unmountController = async (controller, stage) => {
                if (!controller) return;

                try {
                    await Promise.race([
                        Promise.resolve().then(() => controller.unmount()),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('Brick unmount timed out')), 1500)),
                    ]);
                } catch (error) {
                    reportBrickError(error, stage);
                }
            };
            const clearCardFields = () => {
                $wire.card_token = null;
                $wire.card_payment_method_id = null;
                $wire.card_issuer_id = null;
                $wire.card_installments = null;
                $wire.card_identification_type = null;
                $wire.card_identification_number = null;
            };
            const removeMount = (mount) => {
                if (mount?.isConnected) mount.remove();
            };
            const invalidatePendingCreation = () => {
                const operation = pendingCreation;

                if (!operation) return null;

                operation.invalidated = true;
                removeMount(operation.mount);

                return operation;
            };
            const destroyCardBrick = () => {
                const current = brickController;
                const mount = controllerMount;
                brickController = null;
                controllerMount = null;

                cleanupQueue = cleanupQueue.catch((error) => {
                    reportBrickError(error, 'cleanup-queue');
                }).then(async () => {
                    if (current) await unmountController(current, 'unmount');

                    removeMount(mount);
                });

                return cleanupQueue;
            };
            const failBrick = (error, stage, generation, mount = null) => {
                if (generation !== brickGeneration) return;

                if (pendingCreation?.mount === mount && pendingCreation.timer) {
                    clearTimeout(pendingCreation.timer);
                }

                reportBrickError(error, stage);
                brickGeneration += 1;
                brickSubmitting = false;

                if (pendingCreation?.mount === mount) invalidatePendingCreation();

                if (controllerMount === mount && brickController) {
                    const current = brickController;
                    brickController = null;
                    controllerMount = null;
                    void unmountController(current, 'unmount');
                }

                removeMount(mount);
                setBrickState(false, true);
                setCheckoutStatus(false, true);
            };
            const waitForMercadoPago = async () => {
                for (let attempt = 0; attempt < 30; attempt++) {
                    if (typeof window.MercadoPago === 'function') return true;
                    await new Promise((resolve) => setTimeout(resolve, 100));
                }

                return false;
            };
            const isCurrentMount = (generation, container, mount) => (
                generation === brickGeneration
                && isCardSelected()
                && mount?.isConnected
                && getContainer() === container
                && container.contains(mount)
            );
            const reconcileCardBrick = async (generation) => {
                const container = getContainer();
                logCardState('container-state');

                if (!isCardSelected()) {
                    const region = getRegion();
                    if (region) region.hidden = true;
                    if (container) container.hidden = true;
                    const staleCreation = invalidatePendingCreation();
                    clearCardFields();
                    brickSubmitting = false;
                    setBrickState(false);
                    setCheckoutStatus(false);
                    await destroyCardBrick();
                    if (staleCreation?.promise) await staleCreation.promise;

                    return;
                }

                if (!container) {
                    setBrickState(false);

                    return;
                }

                const region = getRegion();
                if (region) region.hidden = false;
                container.hidden = false;

                if (generation !== brickGeneration) return;

                if (brickController && controllerMount?.isConnected && container.contains(controllerMount)) {
                    setBrickState(true);

                    return;
                }

                const previousCreation = pendingCreation;

                if (previousCreation?.generation === generation && !previousCreation.invalidated) {
                    return previousCreation.promise;
                }

                if (previousCreation) {
                    invalidatePendingCreation();
                    await previousCreation.promise;

                    if (generation !== brickGeneration || !isCardSelected()) return;
                }

                await destroyCardBrick();

                if (generation !== brickGeneration || !isCardSelected()) return;

                setBrickState(false);
                setCheckoutStatus(false);

                if (!hasUsablePublicKey) {
                    diagnostic('public-key-present', false);
                    failBrick(new Error('Public key unavailable'), 'public-key', generation);
                    return;
                }

                diagnostic('public-key-present', true);
                diagnostic('public-key-length', paymentPublicKey.trim().length);
                const containerRect = container.getBoundingClientRect();
                const containerStyle = window.getComputedStyle(container);
                const containerVisible = container.isConnected
                    && containerStyle.display !== 'none'
                    && containerStyle.visibility !== 'hidden'
                    && containerRect.width > 0;
                diagnostic('container-present', Boolean(container.isConnected));
                diagnostic('container-connected', container.isConnected);
                diagnostic('container-visible', containerVisible);
                diagnostic('container-size', { width: Math.round(containerRect.width), height: Math.round(containerRect.height) });
                if (!containerVisible) {
                    failBrick(new Error('Brick container is not visible'), 'container-hidden', generation);
                    return;
                }
                const amount = Number(@js($totalCents / 100));
                diagnostic('amount', amount);
                if (!Number.isFinite(amount) || amount <= 0) {
                    failBrick(new Error('Invalid checkout amount'), 'amount', generation);
                    return;
                }
                if (!await waitForMercadoPago()) {
                    diagnostic('sdk-present', false);
                    failBrick(new Error('MercadoPago SDK unavailable'), 'sdk-load', generation);

                    return;
                }

                diagnostic('sdk-present', true);
                if (generation !== brickGeneration || !isCardSelected() || getContainer() !== container) return;

                try {
                    mercadoPagoInstance ??= new window.MercadoPago(paymentPublicKey, { locale: 'pt-BR' });
                    diagnostic('mp-created');
                } catch (error) {
                    failBrick(error, 'sdk-initialize', generation);

                    return;
                }

                const mount = document.createElement('div');
                mount.id = 'cardPaymentBrick_container';
                mount.dataset.generation = String(generation) + '-' + (++mountSequence);
                container.replaceChildren(mount);
                const mountRect = mount.getBoundingClientRect();
                const mountVisible = mount.isConnected && mountRect.width > 0;
                diagnostic('container-present', document.querySelectorAll('#cardPaymentBrick_container').length === 1);
                diagnostic('container-connected', mount.isConnected);
                diagnostic('container-visible', mountVisible);
                diagnostic('container-size', { width: Math.round(mountRect.width), height: Math.round(mountRect.height) });
                if (!mountVisible) {
                    failBrick(new Error('Card Brick mount is not measurable'), 'container-hidden', generation, mount);
                    return;
                }

                const operation = { generation, mount, promise: null, timer: null, ready: false, created: false };
                pendingCreation = operation;
                let rejectTimeout;
                const timeoutPromise = new Promise((_, reject) => { rejectTimeout = reject; });
                operation.timer = setTimeout(() => {
                    operation.timedOut = true;
                    const timeoutError = new Error('Card Payment Brick did not become ready within 5 seconds');
                    rejectTimeout(timeoutError);
                    diagnostic('timeout', { stage: operation.created ? 'brick-ready' : 'brick-create' });
                    if (operation.created) failBrick(timeoutError, 'brick-ready-timeout', generation, mount);
                }, 5000);
                operation.promise = (async () => {
                    try {
                        const bricksBuilder = mercadoPagoInstance.bricks();
                        diagnostic('bricks-builder-created', { present: Boolean(bricksBuilder) });
                        diagnostic('create-start', { brick: 'cardPayment', containerId: mount.id });
                        const creationPromise = bricksBuilder.create('cardPayment', mount.id, {
                            initialization: {
                                amount,
                                payer: { email: $wire.customer_email || '' },
                            },
                            customization: { paymentMethods: { maxInstallments: 12 } },
                            callbacks: {
                                onReady: () => {
                                    diagnostic('on-ready');
                                    operation.ready = true;
                                    if (operation.created && operation.timer) {
                                        clearTimeout(operation.timer);
                                        operation.timer = null;
                                    }
                                    if (isCurrentMount(generation, container, mount)) {
                                        setBrickState(true);
                                        setCheckoutStatus(true);
                                    }
                                },
                                onSubmit: async (formData) => {
                                    if (brickSubmitting || !isCurrentMount(generation, container, mount)) {
                                        return Promise.reject();
                                    }

                                    const token = formData?.token;
                                    const paymentMethodId = formData?.payment_method_id;

                                    if (!token || !paymentMethodId) {
                                        $wire.checkoutError = 'Não foi possível validar o cartão. Confira os dados e tente novamente.';

                                        return Promise.reject();
                                    }

                                    brickSubmitting = true;
                                    $wire.card_token = token;
                                    $wire.card_payment_method_id = paymentMethodId;
                                    $wire.card_installments = Number(formData.installments || 1);
                                    $wire.card_issuer_id = formData.issuer_id ? String(formData.issuer_id) : null;
                                    $wire.card_identification_type = formData.payer?.identification?.type || null;
                                    $wire.card_identification_number = formData.payer?.identification?.number || null;

                                    try {
                                        const result = await $wire.placeOrder();

                                        if ($wire.checkoutError) {
                                            brickSubmitting = false;

                                            return Promise.reject();
                                        }

                                        return result;
                                    } catch (error) {
                                        brickSubmitting = false;
                                        $wire.checkoutError = 'Não foi possível finalizar o pagamento. Tente novamente.';

                                        return Promise.reject();
                                    }
                                },
                                onError: (error) => {
                                    diagnostic('on-error-callback');
                                    rejectTimeout(error instanceof Error ? error : new Error(error?.message || 'Brick reported an error'));
                                    failBrick(error, 'brick-callback', generation, mount);
                                },
                            },
                        });
                        const observedCreationPromise = creationPromise.then((lateController) => {
                            diagnostic('create-resolved');
                            if (operation.timedOut || generation !== brickGeneration || !isCurrentMount(generation, container, mount)) {
                                void unmountController(lateController, 'late-unmount');
                            }
                        });
                        const controller = await Promise.race([observedCreationPromise, timeoutPromise]);
                        operation.created = true;
                        if (operation.ready && operation.timer) {
                            clearTimeout(operation.timer);
                            operation.timer = null;
                        }

                        if (!isCurrentMount(generation, container, mount)) {
                            try {
                                await controller.unmount();
                            } catch (error) {
                                reportBrickError(error, 'stale-unmount');
                            }

                            removeMount(mount);
                            if (pendingCreation === operation) pendingCreation = null;

                            return;
                        }

                        brickController = controller;
                        controllerMount = mount;
                        if (pendingCreation === operation) pendingCreation = null;
                        if (operation.ready) setBrickState(true);
                    } catch (error) {
                        operation.timedOut = true;
                        if (operation.timer) {
                            clearTimeout(operation.timer);
                            operation.timer = null;
                        }
                        if (pendingCreation === operation) pendingCreation = null;

                        if (generation === brickGeneration && isCardSelected()) {
                            failBrick(error, 'brick-create-or-ready', generation, mount);
                        } else {
                            removeMount(mount);
                        }
                    }
                })();

                return operation.promise;
            };
            const syncCardBrick = (generation) => {
                if (reconcilePromise && reconcileGeneration === generation) return reconcilePromise;

                const promise = reconcileCardBrick(generation).catch((error) => {
                    failBrick(error, 'reconcile', generation);
                });
                reconcilePromise = promise;
                reconcileGeneration = generation;

                return promise.finally(() => {
                    if (reconcilePromise === promise) {
                        reconcilePromise = null;
                        reconcileGeneration = null;
                    }
                });
            };
            const retryCardBrick = async () => {
                if (!isCardSelected()) return;

                const generation = ++brickGeneration;
                invalidatePendingCreation();
                clearCardFields();
                brickSubmitting = false;
                setBrickState(false);
                await destroyCardBrick();

                if (generation === brickGeneration) await syncCardBrick(generation);
            };

            root.addEventListener('click', (event) => {
                const target = event.target;
                const retryButton = target && typeof target.closest === 'function'
                    ? target.closest('#paymentBrick_retry')
                    : null;

                if (!retryButton) return;

                event.preventDefault();
                void retryCardBrick();
            });

            $wire.$watch('payment_method', (value) => {
                const generation = ++brickGeneration;
                brickSubmitting = false;

                if (value !== 'credit_card') clearCardFields();

                setCheckoutStatus(value !== 'credit_card');
                diagnostic('payment-method', ['pix', 'credit_card', 'debit_card'].includes(value) ? value : 'unexpected');
                void syncCardBrick(generation);
            });
            if (!window.rochaMercadoPagoBrickNavigationBound) {
                window.rochaMercadoPagoBrickNavigationBound = true;
                document.addEventListener('livewire:navigating', () => {
                    if (typeof window.rochaMercadoPagoBrickCleanup === 'function') {
                        window.rochaMercadoPagoBrickCleanup();
                    }
                });
            }

            window.rochaMercadoPagoBrickCleanup = () => {
                brickGeneration += 1;
                invalidatePendingCreation();
                clearCardFields();
                brickSubmitting = false;
                void destroyCardBrick();
            };

            diagnostic('payment-method', ['pix', 'credit_card', 'debit_card'].includes($wire.payment_method) ? $wire.payment_method : 'unexpected');
            logCardState('initial-state');
            setCheckoutStatus(!isCardSelected());
            void syncCardBrick(brickGeneration);
        @endscript
    @endif
</div>
