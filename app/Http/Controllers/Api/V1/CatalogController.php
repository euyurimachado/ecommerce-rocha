<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogController extends Controller
{
    public function home(): array
    {
        return [
            'data' => [
                'banner' => BannerResource::make(
                    Banner::query()
                        ->where('placement', 'home_hero')
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->first(),
                ),
                'categories' => CategoryResource::collection($this->featuredCategories()->get()),
                'best_sellers' => ProductResource::collection($this->activeProducts()->orderByDesc('sales_count')->take(6)->get()),
                'offers' => ProductResource::collection($this->activeProducts()->where('is_offer', true)->orderByDesc('sales_count')->take(6)->get()),
                'brands' => BrandResource::collection($this->featuredBrands()->get()),
            ],
        ];
    }

    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
        );
    }

    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection(
            Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        );
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $query = trim((string) $request->query('q', ''));
        $categorySlug = $request->query('category');
        $brandSlug = $request->query('brand');
        $sort = (string) $request->query('sort', 'relevance');

        $products = $this->activeProducts()
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $builder->where(function (Builder $search) use ($query): void {
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
            'lowest_price' => $products->orderBy('price_cents'),
            'highest_price' => $products->orderByDesc('price_cents'),
            'best_sellers' => $products->orderByDesc('sales_count'),
            'offers' => $products->orderByDesc('is_offer')->orderByDesc('sales_count'),
            default => $products->orderByDesc('is_featured')->orderByDesc('sales_count'),
        };

        return ProductResource::collection($products->paginate(12));
    }

    public function product(Product $product): JsonResource
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category']);

        return ProductResource::make($product);
    }

    private function activeProducts(): Builder
    {
        return Product::query()
            ->with(['brand', 'category'])
            ->where('is_active', true);
    }

    private function featuredCategories(): Builder
    {
        return Category::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order');
    }

    private function featuredBrands(): Builder
    {
        return Brand::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('name');
    }
}
