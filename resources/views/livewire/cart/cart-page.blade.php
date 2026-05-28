<div>
    @if ($items->isEmpty())
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            <p class="font-semibold text-slate-950">Seu carrinho ainda está vazio.</p>
            <p class="mt-2">Escolha seus suplementos favoritos e adicione ao carrinho com um clique.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-sky-600 px-5 py-3 font-black text-white">Continuar comprando</a>
        </div>
    @else
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-3">
                @foreach ($items as $item)
                    @php($product = $item['product'])
                    <article class="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[5rem_1fr_auto]">
                        <div class="grid aspect-square place-items-center rounded-lg bg-slate-100 text-center">
                            <span class="text-xs font-black text-sky-700">{{ $product->category->icon ?? 'RS' }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-sky-700">{{ $product->brand?->name }}</p>
                            <a href="{{ route('products.show', $product) }}" class="mt-1 block font-black text-slate-950">{{ $product->name }}</a>
                            <p class="mt-1 text-sm text-slate-500">{{ $product->weight }}{{ $product->flavor ? ' - '.$product->flavor : '' }}</p>
                            <button wire:click="remove({{ $product->id }})" class="mt-3 text-sm font-bold text-rose-700" type="button">Remover</button>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                            <div class="flex h-10 items-center rounded-lg border border-slate-200">
                                <button wire:click="decrement({{ $product->id }})" class="grid size-10 place-items-center font-black" type="button" aria-label="Diminuir quantidade">-</button>
                                <span class="w-8 text-center text-sm font-black">{{ $item['quantity'] }}</span>
                                <button wire:click="increment({{ $product->id }})" class="grid size-10 place-items-center font-black" type="button" aria-label="Aumentar quantidade">+</button>
                            </div>
                            <p class="font-black text-slate-950">R$ {{ number_format($item['line_total_cents'] / 100, 2, ',', '.') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black">Resumo</h2>
                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="font-bold">{{ $subtotal }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Entrega</span>
                        <span class="font-bold">Calcular na finalização</span>
                    </div>
                </div>
                <a href="{{ route('checkout') }}" class="mt-6 flex w-full justify-center rounded-lg bg-sky-600 px-5 py-3 font-black text-white">Finalizar compra</a>
                <button wire:click="clear" class="mt-3 w-full rounded-lg border border-slate-200 px-5 py-3 text-sm font-black text-slate-700" type="button">Limpar carrinho</button>
            </aside>
        </div>
    @endif
</div>
