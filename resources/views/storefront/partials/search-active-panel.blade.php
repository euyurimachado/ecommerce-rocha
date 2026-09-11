@if ($popularTerms->isNotEmpty())
    @include('storefront.partials.search-popular-terms')
@endif

<section class="mt-9 hidden" aria-labelledby="recent-searches-title" data-recent-searches>
    <div class="flex items-center justify-between gap-4">
        <h2 id="recent-searches-title" class="text-xl font-bold text-slate-950">Buscas recentes</h2>
        <button type="button" class="text-sm font-bold text-rocha-blue" data-search-clear-history>Limpar</button>
    </div>
    <ul class="mt-3 divide-y divide-slate-100" data-search-history-list></ul>
</section>
