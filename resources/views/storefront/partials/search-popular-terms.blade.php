<section aria-labelledby="popular-searches-title">
    <h2 id="popular-searches-title" class="text-xl font-bold text-slate-950">Em alta</h2>
    <div class="mt-4 flex flex-wrap gap-2.5">
        @foreach ($popularTerms as $category)
            <a href="{{ route('search', ['q' => $category->name]) }}" class="rounded-full bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-rocha-blue/10 hover:text-rocha-blue active:scale-[.98]" data-search-term="{{ $category->name }}">{{ $category->name }}</a>
        @endforeach
    </div>
</section>
