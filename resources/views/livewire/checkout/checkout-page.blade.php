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
                    <div class="mt-5 grid gap-3">
                        @if (! \App\Models\IntegrationSetting::active('payment'))
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-rocha-blue/30 bg-rocha-blue/5 p-4">
                                <input wire:model.live="payment_method" class="mt-1" type="radio" value="mercado_pago">
                                <span><span class="block font-bold text-slate-950">Pagar com Mercado Pago</span><span class="mt-1 block text-sm text-slate-600">Você será direcionado ao ambiente seguro do Mercado Pago.</span></span>
                            </label>
                        @endif
                        @if ($paymentCapabilities->pix)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                                <input wire:model.live="payment_method" class="mt-1" type="radio" value="pix">
                                <span><span class="block font-bold">PIX</span><span class="text-sm text-slate-600">QR Code e código copia e cola. O pedido só será pago após confirmação.</span></span>
                            </label>
                        @endif
                        @if ($paymentCapabilities->creditCard)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                                <input wire:model.live="payment_method" class="mt-1" type="radio" value="credit_card">
                                <span><span class="block font-bold">Cartão de crédito</span><span class="text-sm text-slate-600">{{ $paymentProvider === 'asaas' ? 'Pagamento no ambiente seguro do Asaas.' : 'Dados tokenizados com MercadoPago.js.' }}</span></span>
                            </label>
                        @endif
                    </div>
                    @if ($payment_method === 'credit_card' && $paymentProvider === 'mercado_pago')
                        <div wire:ignore id="paymentBrick_container" class="mt-4"></div>
                        <input wire:model="card_token" type="hidden">
                        @error('card_token') <span class="mt-2 block text-sm text-rose-700">Não foi possível tokenizar o cartão. Revise os dados.</span> @enderror
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
                @if (! ($payment_method === 'credit_card' && $paymentProvider === 'mercado_pago'))
                    <button wire:loading.attr="disabled" wire:target="placeOrder" class="mt-6 flex w-full justify-center rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white disabled:cursor-wait disabled:opacity-70" type="submit">
                        <span wire:loading.remove wire:target="placeOrder">Finalizar pedido</span>
                        <span wire:loading wire:target="placeOrder">Finalizando...</span>
                    </button>
                @else
                    <p class="mt-6 text-center text-sm text-slate-600">Finalize pelo botão seguro exibido no formulário do cartão.</p>
                @endif
            </aside>
        </form>
    @endif

    @if ($paymentProvider === 'mercado_pago' && $paymentPublicKey)
        @assets
            <script src="https://sdk.mercadopago.com/js/v2"></script>
        @endassets
        @script
            let brickController;
            const mountCardBrick = async () => {
                if ($wire.payment_method !== 'credit_card' || ! document.getElementById('paymentBrick_container') || typeof MercadoPago === 'undefined') return;
                if (brickController) await brickController.unmount();
                const mp = new MercadoPago(@js($paymentPublicKey), { locale: 'pt-BR' });
                brickController = await mp.bricks().create('cardPayment', 'paymentBrick_container', {
                    initialization: { amount: @js($totalCents / 100), payer: { email: $wire.customer_email || '' } },
                    customization: { paymentMethods: { maxInstallments: 12 } },
                    callbacks: {
                        onSubmit: ({ formData }) => {
                            $wire.card_token = formData.token;
                            $wire.card_payment_method_id = formData.payment_method_id;
                            $wire.card_installments = Number(formData.installments || 1);
                            return $wire.placeOrder();
                        },
                        onError: () => { $wire.checkoutError = 'Não foi possível validar o cartão. Revise os dados.'; },
                    },
                });
            };
            $wire.$watch('payment_method', () => setTimeout(mountCardBrick, 0));
            mountCardBrick();
        @endscript
    @endif
</div>
