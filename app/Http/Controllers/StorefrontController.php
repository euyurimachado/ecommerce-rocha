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
                    ->orderByDesc('stock_quantity')])
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
                ->where('is_featured', true)
                ->orderBy('name')
                ->get(),
            'energyProducts' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->whereHas('category', fn (Builder $category) => $category->whereIn('slug', ['energia', 'pre-treino']))
                ->orderByDesc('stock_quantity')
                ->take(8)
                ->get(),
            'massProducts' => Product::query()
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
            'wheyFestival' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->whereHas('category', fn (Builder $category) => $category->where('slug', 'whey-protein'))
                ->orderBy('price_cents')
                ->take(10)
                ->get(),
            'creatineHouse' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->whereHas('category', fn (Builder $category) => $category->where('slug', 'creatina'))
                ->orderByDesc('stock_quantity')
                ->take(5)
                ->get(),
            'weightLossProducts' => Product::query()
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
        ]);
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
        $query = trim((string) $request->query('q', ''));
        $categorySlug = $request->query('categoria');
        $brandSlug = $request->query('marca');
        $sort = (string) $request->query('ordenar', 'relevancia');

        $productsQuery = Product::query()
            ->with(['brand', 'category'])
            ->where('is_active', true)
            ->when($query !== '', function (Builder $builder) use ($query) {
                $builder->where(function (Builder $search) use ($query) {
                    $search
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$query}%"));
                });
            })
            ->when($categorySlug, fn (Builder $builder) => $builder->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug)))
            ->when($brandSlug, fn (Builder $builder) => $builder->whereHas('brand', fn (Builder $brand) => $brand->where('slug', $brandSlug)));

        match ($sort) {
            'menor-preco' => $productsQuery->orderBy('price_cents'),
            'maior-preco' => $productsQuery->orderByDesc('price_cents'),
            'mais-vendidos' => $productsQuery->orderByDesc('sales_count'),
            'ofertas' => $productsQuery->orderByDesc('is_offer')->orderByDesc('sales_count'),
            default => $productsQuery->orderByDesc('is_featured')->orderByDesc('sales_count'),
        };

        return view('storefront.search', [
            'query' => $query,
            'selectedCategory' => $categorySlug,
            'selectedBrand' => $brandSlug,
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
            'popularSearches' => ['whey protein', 'creatina', 'pré-treino', 'vitaminas', 'combos'],
        ]);
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
