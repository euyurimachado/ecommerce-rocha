<div>
    @if ($items->isEmpty())
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            <p class="font-semibold text-slate-950">Seu carrinho ainda está vazio.</p>
            <p class="mt-2">Adicione produtos antes de finalizar a compra.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-black text-white">Continuar comprando</a>
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
                    <h2 class="text-xl font-black">1. Identificação</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Nome completo</span>
                            <input wire:model="customer_name" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="name">
                            @error('customer_name') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Telefone / WhatsApp</span>
                            <input wire:model="customer_phone" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="tel" autocomplete="tel">
                            @error('customer_phone') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-sm font-bold text-slate-700">E-mail</span>
                            <input wire:model="customer_email" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="email" autocomplete="email">
                            @error('customer_email') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black">2. Entrega ou retirada</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model.live="fulfillment_method" class="mt-1" type="radio" value="delivery">
                            <span>
                                <span class="block font-black">Entrega local</span>
                                <span class="mt-1 block text-sm text-slate-600">Receba em Campos dos Goytacazes.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model.live="fulfillment_method" class="mt-1" type="radio" value="pickup">
                            <span>
                                <span class="block font-black">Retirada na loja</span>
                                <span class="mt-1 block text-sm text-slate-600">Separaremos o pedido para retirada.</span>
                            </span>
                        </label>
                    </div>

                    @if ($fulfillment_method === 'delivery')
                        <div class="mt-5 grid gap-4 md:grid-cols-6">
                            <label class="block md:col-span-2">
                                <span class="text-sm font-bold text-slate-700">CEP</span>
                                <input wire:model="postal_code" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="postal-code">
                                @error('postal_code') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-3">
                                <span class="text-sm font-bold text-slate-700">Rua</span>
                                <input wire:model="street" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text" autocomplete="address-line1">
                                @error('street') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-1">
                                <span class="text-sm font-bold text-slate-700">Número</span>
                                <input wire:model="number" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text">
                                @error('number') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-2">
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
                                <input wire:model="state" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 uppercase outline-none focus:border-rocha-blue" type="text" maxlength="2">
                                @error('state') <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span> @enderror
                            </label>
                            <label class="block md:col-span-1">
                                <span class="text-sm font-bold text-slate-700">Compl.</span>
                                <input wire:model="complement" class="mt-2 h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-rocha-blue" type="text">
                            </label>
                        </div>
                    @else
                        <div class="mt-5 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                            Retirada na Rocha Sports. A equipe confirmará o horário pelo WhatsApp após o pedido.
                        </div>
                    @endif
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black">3. Pagamento</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model="payment_method" class="mt-1" type="radio" value="pix">
                            <span class="font-black">Pix</span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model="payment_method" class="mt-1" type="radio" value="credit_card">
                            <span class="font-black">Cartão</span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-4">
                            <input wire:model="payment_method" class="mt-1" type="radio" value="boleto">
                            <span class="font-black">Boleto</span>
                        </label>
                    </div>
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
                <h2 class="text-xl font-black">Resumo</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($items as $item)
                        @php($product = $item['product'])
                        <div class="flex gap-3 text-sm">
                            <div class="grid size-12 place-items-center rounded-md bg-slate-100 text-xs font-black text-rocha-blue">{{ $product->category->icon ?? 'RS' }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-950">{{ $product->name }}</p>
                                <p class="mt-1 text-slate-500">{{ $item['quantity'] }} x {{ $product->formatted_price }}</p>
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
                        <span class="font-bold">A combinar</span>
                    </div>
                    <div class="mt-4 flex justify-between text-lg">
                        <span class="font-black">Total</span>
                        <span class="font-black">{{ $subtotal }}</span>
                    </div>
                </div>
                <button wire:loading.attr="disabled" wire:target="placeOrder" class="mt-6 flex w-full justify-center rounded-lg bg-rocha-blue px-5 py-3 font-black text-white disabled:cursor-wait disabled:opacity-70" type="submit">
                    <span wire:loading.remove wire:target="placeOrder">Finalizar pedido</span>
                    <span wire:loading wire:target="placeOrder">Finalizando...</span>
                </button>
            </aside>
        </form>
    @endif
</div>
