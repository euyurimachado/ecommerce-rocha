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
            'banner' => Banner::query()
                ->where('placement', 'home_hero')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first(),
            'categories' => Category::query()
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

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('storefront.category', [
            'category' => $category,
            'products' => Product::query()
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->where('category_id', $category->id)
                ->orderByDesc('sales_count')
                ->paginate(12),
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
            'popularSearches' => ['whey protein', 'creatina', 'pre-treino', 'vitaminas', 'combos'],
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
