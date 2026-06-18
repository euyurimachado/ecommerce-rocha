<div>
    @if ($items->isEmpty())
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            <p class="font-semibold text-slate-950">Seu carrinho ainda está vazio.</p>
            <p class="mt-2">Escolha seus suplementos favoritos e adicione ao carrinho com um clique.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white">Continuar comprando</a>
        </div>
    @else
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-3">
                @foreach ($items as $item)
                    @php($product = $item['product'])
                    <article class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[5rem_1fr_auto]">
                        <div class="grid aspect-square place-items-center rounded-lg bg-slate-100 text-center">
                            <span class="text-xs font-bold text-rocha-blue">{{ $product->category->icon ?? 'RS' }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-rocha-blue">{{ $product->brand?->name }}</p>
                            <a href="{{ route('products.show', $product) }}" class="mt-1 block font-semibold leading-snug text-slate-950">{{ $product->name }}</a>
                            @if ($item['variant_summary'])
                                <p class="mt-1 text-sm font-semibold text-slate-600">{{ $item['variant_summary'] }}</p>
                            @else
                                <p class="mt-1 text-sm text-slate-500">{{ $product->weight }}</p>
                            @endif
                            <button wire:click="remove('{{ $item['key'] }}')" class="mt-3 text-sm font-bold text-rose-700" type="button">Remover</button>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                            <div class="flex h-10 items-center rounded-lg border border-slate-200">
                                <button wire:click="decrement('{{ $item['key'] }}')" class="grid size-10 place-items-center font-bold" type="button" aria-label="Diminuir quantidade">-</button>
                                <span class="w-8 text-center text-sm font-bold">{{ $item['quantity'] }}</span>
                                <button wire:click="increment('{{ $item['key'] }}')" class="grid size-10 place-items-center font-bold" type="button" aria-label="Aumentar quantidade">+</button>
                            </div>
                            <p class="font-bold text-slate-950">R$ {{ number_format($item['line_total_cents'] / 100, 2, ',', '.') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold md:text-xl">Resumo</h2>
                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="font-bold">{{ $subtotal }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Entrega</span>
                        <span class="font-bold">A partir de R$ {{ number_format(config('commerce.shipping.local_delivery_fee_cents') / 100, 2, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-slate-500">Retirada grátis na loja. Frete grátis acima de R$ {{ number_format(config('commerce.shipping.free_shipping_threshold_cents') / 100, 2, ',', '.') }}.</p>
                    @if ($coupon)
                        <div class="flex justify-between text-emerald-700">
                            <span>Cupom {{ $coupon->code }}</span>
                            <span class="font-bold">- {{ $discount }}</span>
                        </div>
                    @endif
                    <div class="border-t border-slate-200 pt-3">
                        <div class="flex justify-between text-lg">
                            <span class="font-bold">Total</span>
                            <span class="font-bold">{{ $total }}</span>
                        </div>
                    </div>
                </div>
                <form wire:submit="applyCoupon" class="mt-5">
                    <label class="text-sm font-bold text-slate-700" for="cart-coupon">Cupom de desconto</label>
                    <div class="mt-2 flex gap-2">
                        <input id="cart-coupon" wire:model="couponCode" class="h-11 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm uppercase outline-none focus:border-rocha-blue" type="text" placeholder="ROCHA10">
                        <button class="rounded-lg border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue" type="submit">Aplicar</button>
                    </div>
                    @if ($coupon)
                        <button wire:click="removeCoupon" class="mt-2 text-sm font-bold text-rose-700" type="button">Remover cupom</button>
                    @endif
                    @if ($couponError)
                        <p class="mt-2 text-sm font-semibold text-rose-700">{{ $couponError }}</p>
                    @endif
                </form>
                <a href="{{ route('checkout') }}" class="mt-6 flex w-full justify-center rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white">Finalizar compra</a>
                <button wire:click="clear" class="mt-3 w-full rounded-lg border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700" type="button">Limpar carrinho</button>
            </aside>
        </div>
    @endif
</div>
