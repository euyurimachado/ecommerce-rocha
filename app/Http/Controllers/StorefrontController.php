<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

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
}
