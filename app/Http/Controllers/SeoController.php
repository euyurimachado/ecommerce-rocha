<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        return response(
            "User-agent: *\nDisallow:\nSitemap: https://rochasports.com.br/sitemap.xml\n",
            200,
            ['Content-Type' => 'text/plain'],
        );
    }

    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now(), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('search'), 'lastmod' => now(), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('legal.privacy'), 'lastmod' => now(), 'priority' => '0.3', 'changefreq' => 'monthly'],
            ['loc' => route('legal.cookies'), 'lastmod' => now(), 'priority' => '0.3', 'changefreq' => 'monthly'],
        ]);

        $categoryUrls = Category::query()
            ->where('is_active', true)
            ->get()
            ->map(fn (Category $category): array => [
                'loc' => route('categories.show', $category),
                'lastmod' => $category->updated_at,
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ]);

        $productUrls = Product::query()
            ->where('is_active', true)
            ->get()
            ->map(fn (Product $product): array => [
                'loc' => route('products.show', $product),
                'lastmod' => $product->updated_at,
                'priority' => '0.9',
                'changefreq' => 'weekly',
            ]);

        return response()
            ->view('seo.sitemap', [
                'urls' => $urls
                    ->merge($categoryUrls)
                    ->merge($productUrls),
            ])
            ->header('Content-Type', 'application/xml');
    }
}
