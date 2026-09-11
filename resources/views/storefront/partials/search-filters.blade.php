<h2 class="font-bold text-slate-950">Filtros</h2>
<form action="{{ route('search') }}" method="GET" class="mt-4 space-y-4">
    <input type="hidden" name="q" value="{{ $query }}">
    @if ($selectedHomeSection)<input type="hidden" name="secao" value="{{ $selectedHomeSection }}">@endif

    <label class="block"><span class="text-sm font-bold text-slate-700">Categoria</span><select name="categoria" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue"><option value="">Todas</option>@foreach ($categories as $category)<option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>@endforeach</select></label>
    <label class="block"><span class="text-sm font-bold text-slate-700">Marca</span><select name="marca" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue"><option value="">Todas</option>@foreach ($brands as $brand)<option value="{{ $brand->slug }}" @selected($selectedBrand === $brand->slug)>{{ $brand->name }}</option>@endforeach</select></label>
    <label class="block"><span class="text-sm font-bold text-slate-700">Ordenar</span><select name="ordenar" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue"><option value="relevancia" @selected($selectedSort === 'relevancia')>Relevância</option><option value="mais-vendidos" @selected($selectedSort === 'mais-vendidos')>Mais vendidos</option><option value="ofertas" @selected($selectedSort === 'ofertas')>Ofertas</option><option value="menor-preco" @selected($selectedSort === 'menor-preco')>Menor preço</option><option value="maior-preco" @selected($selectedSort === 'maior-preco')>Maior preço</option></select></label>

    <button class="w-full rounded-lg bg-slate-950 px-4 py-3 text-sm font-bold text-white" type="submit">Aplicar filtros</button>
    <a href="{{ route('search') }}" class="block text-center text-sm font-bold text-slate-600">Limpar</a>
</form>
