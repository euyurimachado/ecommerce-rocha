<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home(): View
    {
        return view('storefront.home', [
            'banners' => Banner::query()
                ->where('placement', 'home_hero')
                ->where('is_active', true)
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->where(fn (Builder $builder) => $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now()))
                ->where(fn (Builder $builder) => $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()))
                ->orderBy('sort_order')
                ->get(),
            'energyBanner' => Banner::query()
                ->where('placement', 'home_energy')
                ->where('is_active', true)
                ->whereNotNull('image_path')
                ->where('image_path', '!=', '')
                ->where(fn (Builder $builder) => $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now()))
                ->where(fn (Builder $builder) => $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()))
                ->orderBy('sort_order')
                ->first(),
            'categories' => Category::query()
                ->with(['products' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('image_path')
                    ->orderByDesc('sales_count')])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->get(),
            'bestSellers' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->orderByDesc('sales_count')
                ->take(6)
                ->get(),
            'offers' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->where('is_offer', true)
                ->orderByDesc('sales_count')
                ->take(6)
                ->get(),
            'brands' => Brand::query()
                ->where('is_active', true)
                ->whereHas('products', fn (Builder $query) => $query->where('is_active', true))
                ->orderBy('name')
                ->get(),
            'energyProducts' => $this->homeSectionProducts(
                'show_in_energy',
                'energy_sort_order',
                8,
                fn () => Product::query()
                    ->with(['brand', 'category'])
                    ->where('is_active', true)
                    ->whereHas('category', fn (Builder $category) => $category->whereIn('slug', ['energia', 'pre-treino']))
                    ->orderByDesc('sales_count')
                    ->take(8)
                    ->get(),
            ),
            'massProducts' => $this->homeSectionProducts(
                'show_in_mass_gain',
                'mass_gain_sort_order',
                6,
                fn () => Product::query()
                    ->with(['brand', 'category'])
                    ->where('is_active', true)
                    ->where(function (Builder $builder) {
                        $builder
                            ->whereHas('category', fn (Builder $category) => $category->whereIn('slug', ['hipercalorico', 'whey-protein', 'creatina']))
                            ->orWhere('name', 'like', '%MASS%');
                    })
                    ->orderByDesc('price_cents')
                    ->take(6)
                    ->get(),
            ),
            'wheyFestival' => $this->homeSectionProducts(
                'show_in_whey_festival',
                'whey_festival_sort_order',
                10,
                fn () => Product::query()
                    ->with(['brand', 'category'])
                    ->where('is_active', true)
                    ->whereHas('category', fn (Builder $category) => $category->where('slug', 'whey-protein'))
                    ->orderBy('price_cents')
                    ->take(10)
                    ->get(),
            ),
            'creatineHouse' => $this->homeSectionProducts(
                'show_in_creatine_house',
                'creatine_house_sort_order',
                5,
                fn () => Product::query()
                    ->with(['brand', 'category'])
                    ->where('is_active', true)
                    ->whereHas('category', fn (Builder $category) => $category->where('slug', 'creatina'))
                    ->orderByDesc('sales_count')
                    ->take(5)
                    ->get(),
            ),
            'weightLossProducts' => $this->homeSectionProducts(
                'show_in_weight_loss',
                'weight_loss_sort_order',
                4,
                fn () => Product::query()
                    ->with(['brand', 'category'])
                    ->where('is_active', true)
                    ->where(function (Builder $builder) {
                        $builder
                            ->where('name', 'like', '%CONTROL%')
                            ->orWhere('name', 'like', '%CAFEINA%')
                            ->orWhere('name', 'like', '%COFFEE%')
                            ->orWhereHas('category', fn (Builder $category) => $category->whereIn('slug', ['termogenico', 'pre-treino']));
                    })
                    ->orderBy('price_cents')
                    ->take(4)
                    ->get(),
            ),
        ]);
    }

    private function homeSectionProducts(string $flagColumn, string $sortColumn, int $limit, callable $fallback)
    {
        $products = Product::query()
            ->with(['brand', 'category'])
            ->where('is_active', true)
            ->where($flagColumn, true)
            ->orderByRaw("{$sortColumn} is null")
            ->orderBy($sortColumn)
            ->orderByDesc('sales_count')
            ->take($limit)
            ->get();

        return $products->isNotEmpty() ? $products : $fallback();
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category']);

        return view('storefront.product', [
            'product' => $product,
            'relatedProducts' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->take(4)
                ->get(),
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $brandSlug = $request->query('marca');
        $sort = (string) $request->query('ordenar', 'relevancia');

        $productsQuery = Product::query()
            ->with(['brand', 'category'])
            ->where('is_active', true)
            ->where('category_id', $category->id)
            ->when($brandSlug, fn (Builder $builder) => $builder->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $brandSlug)));

        match ($sort) {
            'menor-preco' => $productsQuery->orderBy('price_cents'),
            'maior-preco' => $productsQuery->orderByDesc('price_cents'),
            'mais-vendidos' => $productsQuery->orderByDesc('sales_count'),
            'ofertas' => $productsQuery->orderByDesc('is_offer')->orderByDesc('sales_count'),
            default => $productsQuery->orderByDesc('is_featured')->orderByDesc('sales_count'),
        };

        return view('storefront.category', [
            'category' => $category,
            'selectedBrand' => $brandSlug,
            'selectedSort' => $sort,
            'brands' => Brand::query()
                ->where('is_active', true)
                ->whereHas('products', fn (Builder $product) => $product
                    ->where('is_active', true)
                    ->where('category_id', $category->id))
                ->orderBy('name')
                ->get(),
            'products' => $productsQuery
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function search(Request $request): View
    {
        $query = preg_replace('/\s+/u', ' ', trim((string) $request->query('q', ''))) ?? '';
        $categorySlug = $request->query('categoria');
        $brandSlug = $request->query('marca');
        $homeSection = (string) $request->query('secao', '');
        $sort = (string) $request->query('ordenar', 'relevancia');
        $homeSectionFilters = [
            'emagrecer' => 'show_in_weight_loss',
            'energia' => 'show_in_energy',
            'massa' => 'show_in_mass_gain',
            'whey' => 'show_in_whey_festival',
            'creatina' => 'show_in_creatine_house',
        ];
        $searchTerms = collect(preg_split('/\s+/u', mb_strtolower($query)) ?: [])
            ->map(fn (string $term): string => trim($term))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 2)
            ->values();

        $primarySearch = Product::query()
            ->where('is_active', true)
            ->when($searchTerms->isNotEmpty(), fn (Builder $builder) => $this->applyPrimarySearch($builder, $searchTerms->all()));
        $useDescriptionFallback = $searchTerms->isNotEmpty() && ! $primarySearch->exists();

        $productsQuery = Product::query()
            ->with(['brand', 'category'])
            ->where('is_active', true)
            ->when($searchTerms->isNotEmpty(), fn (Builder $builder) => $useDescriptionFallback
                ? $this->applyDescriptionFallback($builder, $searchTerms->all())
                : $this->applyPrimarySearch($builder, $searchTerms->all()))
            ->when($categorySlug, fn (Builder $builder) => $builder->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug)))
            ->when($brandSlug, fn (Builder $builder) => $builder->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $brandSlug)))
            ->when(
                array_key_exists($homeSection, $homeSectionFilters),
                fn (Builder $builder) => $builder->where($homeSectionFilters[$homeSection], true),
            );

        match ($sort) {
            'menor-preco' => $productsQuery->orderBy('price_cents'),
            'maior-preco' => $productsQuery->orderByDesc('price_cents'),
            'mais-vendidos' => $productsQuery->orderByDesc('sales_count'),
            'ofertas' => $productsQuery->orderByDesc('is_offer')->orderByDesc('sales_count'),
            default => $searchTerms->isNotEmpty() && ! $useDescriptionFallback
                ? $this->applyRelevanceOrder($productsQuery, $query, $searchTerms->all())
                : $productsQuery->orderByDesc('is_featured')->orderByDesc('sales_count'),
        };

        return view('storefront.search', [
            'query' => $query,
            'selectedCategory' => $categorySlug,
            'selectedBrand' => $brandSlug,
            'selectedHomeSection' => $homeSection,
            'selectedSort' => $sort,
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'products' => $productsQuery
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    private function applyPrimarySearch(Builder $builder, array $terms): Builder
    {
        foreach ($terms as $term) {
            $like = '%'.$term.'%';
            $builder->where(fn (Builder $search) => $search
                ->whereRaw('LOWER(name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(slug) LIKE ?', [$like])
                ->orWhereRaw('LOWER(sku) LIKE ?', [$like])
                ->orWhereHas('brand', fn (Builder $brand) => $brand
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$like]))
                ->orWhereHas('category', fn (Builder $category) => $category
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$like])));
        }

        return $builder;
    }

    private function applyDescriptionFallback(Builder $builder, array $terms): Builder
    {
        foreach ($terms as $term) {
            $like = '%'.$term.'%';
            $builder->where(fn (Builder $search) => $search
                ->whereRaw('LOWER(short_description) LIKE ?', [$like])
                ->orWhereRaw('LOWER(description) LIKE ?', [$like]));
        }

        return $builder;
    }

    private function applyRelevanceOrder(Builder $builder, string $query, array $terms): Builder
    {
        $normalized = mb_strtolower($query);

        return $builder
            ->orderByRaw(
                'CASE WHEN LOWER(name) = ? THEN 0 WHEN LOWER(name) LIKE ? THEN 1 WHEN LOWER(name) LIKE ? THEN 2 ELSE 3 END',
                [$normalized, $normalized.'%', '%'.implode('%', $terms).'%'],
            )
            ->orderByDesc('is_featured')
            ->orderByDesc('sales_count');
    }

    public function orders(Request $request): View
    {
        $validated = $request->validate([
            'contato' => ['nullable', 'string', 'max:120'],
        ]);

        $contact = trim((string) ($validated['contato'] ?? ''));
        $normalizedPhone = preg_replace('/\D+/', '', $contact);

        $orders = collect();

        if ($contact !== '') {
            $orders = Order::query()
                ->with('items')
                ->where(function (Builder $builder) use ($contact, $normalizedPhone) {
                    $builder
                        ->whereRaw('LOWER(customer_email) = ?', [mb_strtolower($contact)])
                        ->orWhere('customer_phone', $contact);

                    if ($normalizedPhone !== '') {
                        $builder->orWhere('customer_phone', $normalizedPhone);
                    }
                })
                ->latest()
                ->take(10)
                ->get();
        }

        return view('storefront.orders', [
            'contact' => $contact,
            'orders' => $orders,
        ]);
    }

    public function orderStatus(Order $order): View
    {
        $order->load('items');

        return view('storefront.order-status', [
            'order' => $order,
        ]);
    }
}
