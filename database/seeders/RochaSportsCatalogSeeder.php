<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RochaSportsCatalogSeeder extends Seeder
{
    private array $brandLogoPaths = [];

    private array $manufacturerUrls = [];

    private array $descriptionCache = [];

    public function run(): void
    {
        $products = $this->normalizeProducts(json_decode(
            file_get_contents(database_path('data/rocha_sports_products.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        ));
        $slugs = collect($products)
            ->keys()
            ->map(fn (int|string $index): string => $this->productSlug(
                $products[$index]['brand'],
                $products[$index]['category'],
                $products[$index]['name'],
                (int) $index,
            ))
            ->all();

        Product::query()
            ->where('sku', 'like', 'RS-%')
            ->whereNotIn('slug', $slugs)
            ->delete();

        foreach ($products as $index => $data) {
            $category = $this->category($data['category'], $index);
            $brand = $this->brand($data['brand']);
            $slug = $this->productSlug($data['brand'], $data['category'], $data['name'], $index);
            $manufacturerUrl = $this->manufacturerUrl($data['brand'], $data['name']);
            $content = $this->productContent($data, $manufacturerUrl);
            $options = collect($data['options']);
            $flavoredOptions = $options
                ->filter(fn (array $option): bool => filled($option['value']))
                ->values();
            $mainImagePath = $this->imagePath($data['brand'], $data['name'], $slug);

            $variations = $flavoredOptions->isEmpty()
                ? null
                : [[
                    'name' => 'Sabor',
                    'options' => $flavoredOptions
                        ->map(function (array $option) use ($data, $slug, $mainImagePath): array {
                            $optionImagePath = $this->imagePath(
                                $data['brand'],
                                $data['name'].' '.$option['value'],
                                Str::slug($slug.' '.$option['value']),
                                false,
                            );

                            return [
                                'value' => $option['value'],
                                'sku' => $this->sku($data['brand'], $data['category'], $data['name'], $option['value']),
                                'price_cents' => $option['price_cents'],
                                'compare_at_price_cents' => $option['compare_at_price_cents'],
                                'image_path' => $optionImagePath ?: $mainImagePath,
                            ];
                        })
                        ->all(),
                ]];

            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'name' => $data['name'],
                    'sku' => $this->sku($data['brand'], $data['category'], $data['name']),
                    'image_path' => $mainImagePath,
                    'gallery_images' => [],
                    'weight' => $this->weight($data['name']),
                    'variations' => $variations,
                    'short_description' => $content['short_description'],
                    'description' => $content['description'],
                    'benefits' => $content['benefits'],
                    'usage_instructions' => $content['usage_instructions'],
                    'ingredients' => $content['ingredients'],
                    'nutrition_facts' => $content['nutrition_facts'],
                    'serving_size' => $content['serving_size'],
                    'allergen_info' => $content['allergen_info'],
                    'manufacturer_url' => $manufacturerUrl,
                    'image_source_url' => null,
                    'price_cents' => (int) $data['price_cents'],
                    'compare_at_price_cents' => $data['compare_at_price_cents'],
                    'rating' => 0,
                    'reviews_count' => 0,
                    'sales_count' => 0,
                    'is_active' => true,
                    'is_featured' => $index < 18,
                    'is_offer' => filled($data['compare_at_price_cents']),
                    'allows_pickup' => true,
                    'allows_local_delivery' => true,
                    'meta_title' => $data['name'].' | Rocha Sports',
                    'meta_description' => 'Compre '.$data['name'].' da '.$data['brand'].' na Rocha Sports com retirada ou entrega local.',
                ],
            );
        }

        $this->seedHomeBanners();
        $this->seedHomeProductSections();
    }

    private function normalizeProducts(array $products): array
    {
        $groups = [];

        foreach ($products as $product) {
            [$name, $embeddedFlavor] = $this->extractEmbeddedFlavor($product['name']);
            $key = Str::slug($product['brand'].'|'.$product['category'].'|'.$name);

            $groups[$key] ??= [
                ...$product,
                'name' => $name,
                'options' => [],
            ];

            foreach ($product['options'] as $option) {
                $value = $embeddedFlavor ?: $option['value'];
                $optionKey = Str::slug((string) ($value ?: 'unico'));

                $groups[$key]['options'][$optionKey] ??= [
                    ...$option,
                    'value' => $value,
                ];

                $groups[$key]['options'][$optionKey]['price_cents'] = min(
                    (int) $groups[$key]['options'][$optionKey]['price_cents'],
                    (int) $option['price_cents'],
                );
            }
        }

        return collect($groups)
            ->map(function (array $product): array {
                $options = collect($product['options'])->values();
                $baseOption = $options->sortBy('price_cents')->first();

                return [
                    ...$product,
                    'price_cents' => $baseOption['price_cents'],
                    'compare_at_price_cents' => $baseOption['compare_at_price_cents'],
                    'options' => $options->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function extractEmbeddedFlavor(string $name): array
    {
        if (! str_contains($name, ' - ')) {
            return [$name, null];
        }

        [$baseName, $suffix] = array_map('trim', explode(' - ', $name, 2));

        if (preg_match('/^(?:LINHA|POTE|LATA|CAIXA|POOL|C\\/|SEM|COM)\\b/i', $suffix)) {
            return [$name, null];
        }

        if (mb_strlen($suffix) > 32) {
            return [$name, null];
        }

        return [$baseName, mb_strtoupper($suffix)];
    }

    private function category(string $name, int $index): Category
    {
        $name = Str::of($name)->squish()->toString();

        return Category::updateOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'icon' => $this->icon($name),
                'short_description' => $this->categoryDescription($name),
                'seo_description' => $name.' selecionados pela Rocha Sports, com produtos originais e atendimento especializado.',
                'meta_title' => $name.' | Rocha Sports',
                'meta_description' => 'Compre '.$name.' na Rocha Sports com retirada ou entrega local.',
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'is_featured' => true,
            ],
        );
    }

    private function brand(string $name): Brand
    {
        $name = Str::of($name)->squish()->toString();

        return Brand::updateOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'logo_path' => $this->brandLogoPath($name),
                'description' => 'Marca com produtos cadastrados a partir da lista comercial Rocha Sports.',
                'is_active' => true,
                'is_featured' => true,
            ],
        );
    }

    private function brandLogoPath(string $name): ?string
    {
        if (array_key_exists($name, $this->brandLogoPaths)) {
            return $this->brandLogoPaths[$name];
        }

        $slug = Str::slug($name);
        $basePath = "brands/imported/{$slug}";

        foreach (['png', 'webp', 'jpg', 'jpeg'] as $extension) {
            $path = "{$basePath}.{$extension}";

            if (Storage::disk('public')->exists($path)) {
                return $this->brandLogoPaths[$name] = $path;
            }
        }

        foreach ($this->imageUrls("{$name} logo suplementos png") as $imageUrl) {
            try {
                $response = Http::timeout(6)
                    ->retry(1, 150)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($imageUrl);

                if (! $response->successful()) {
                    continue;
                }

                $extension = $this->extensionFromResponse($response->header('Content-Type'), $imageUrl);
                $path = "{$basePath}.{$extension}";

                Storage::disk('public')->put($path, $response->body());

                return $this->brandLogoPaths[$name] = $path;
            } catch (Throwable) {
                continue;
            }
        }

        return $this->brandLogoPaths[$name] = null;
    }

    private function seedHomeBanners(): void
    {
        $banners = [
            [
                'placement' => 'home_hero',
                'sort_order' => 1,
                'title' => 'Festival de Whey Protein',
                'subtitle' => 'Proteínas DUX, Probiótica, Body Action e mais marcas para manter a rotina no trilho.',
                'cta_label' => 'Ver whey protein',
                'url' => route('search', ['categoria' => 'whey-protein']),
            ],
            [
                'placement' => 'home_hero',
                'sort_order' => 2,
                'title' => 'Rocha Sports, a casa da creatina',
                'subtitle' => 'Potes, sticks e fórmulas Creapure para força, potência e consistência diária.',
                'cta_label' => 'Comprar creatina',
                'url' => route('search', ['categoria' => 'creatina']),
            ],
            [
                'placement' => 'home_hero',
                'sort_order' => 3,
                'title' => 'Energia para treinar forte',
                'subtitle' => 'Pré-treinos, géis e reposição para treinos intensos, provas e rotina corrida.',
                'cta_label' => 'Ver energia',
                'url' => route('search', ['categoria' => 'energia']),
            ],
            [
                'placement' => 'home_energy',
                'sort_order' => 1,
                'title' => 'Para ter energia',
                'subtitle' => 'Banner editável da seção de energia.',
                'cta_label' => 'Ver energia',
                'url' => route('search', ['categoria' => 'energia']),
            ],
        ];

        foreach ($banners as $bannerData) {
            $banner = Banner::firstOrNew([
                'placement' => $bannerData['placement'],
                'sort_order' => $bannerData['sort_order'],
            ]);

            $imagePath = $banner->exists
                ? $banner->image_path
                : null;

            if (str_starts_with((string) $imagePath, 'banners/imported/home-offer-')) {
                $imagePath = null;
            }

            $banner->fill([
                'title' => $bannerData['title'],
                'subtitle' => $bannerData['subtitle'],
                'cta_label' => $bannerData['cta_label'],
                'url' => $bannerData['url'],
                'image_path' => $imagePath,
                'device' => $banner->exists ? $banner->device : 'all',
                'is_active' => $banner->exists ? $banner->is_active : true,
                'starts_at' => $banner->exists ? $banner->starts_at : null,
                'ends_at' => $banner->exists ? $banner->ends_at : null,
            ])->save();
        }
    }

    private function seedHomeProductSections(): void
    {
        if (! Schema::hasColumn('products', 'show_in_energy')) {
            return;
        }

        $hasHomeCuration = Product::query()
            ->where(function ($query) {
                $query
                    ->where('show_in_weight_loss', true)
                    ->orWhere('show_in_energy', true)
                    ->orWhere('show_in_mass_gain', true)
                    ->orWhere('show_in_whey_festival', true)
                    ->orWhere('show_in_creatine_house', true);
            })
            ->exists();

        if ($hasHomeCuration) {
            return;
        }

        $this->curateHomeSection(
            'show_in_weight_loss',
            'weight_loss_sort_order',
            Product::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query
                        ->where('name', 'like', '%CONTROL%')
                        ->orWhere('name', 'like', '%CAFEINA%')
                        ->orWhere('name', 'like', '%COFFEE%')
                        ->orWhereHas('category', fn ($category) => $category->whereIn('slug', ['termogenico', 'pre-treino']));
                })
                ->orderBy('price_cents')
                ->take(4)
                ->pluck('id')
                ->all(),
        );

        $this->curateHomeSection(
            'show_in_energy',
            'energy_sort_order',
            Product::query()
                ->where('is_active', true)
                ->whereHas('category', fn ($category) => $category->whereIn('slug', ['energia', 'pre-treino']))
                ->orderByDesc('sales_count')
                ->take(8)
                ->pluck('id')
                ->all(),
        );

        $this->curateHomeSection(
            'show_in_mass_gain',
            'mass_gain_sort_order',
            Product::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query
                        ->whereHas('category', fn ($category) => $category->whereIn('slug', ['hipercalorico', 'whey-protein', 'creatina']))
                        ->orWhere('name', 'like', '%MASS%');
                })
                ->orderByDesc('price_cents')
                ->take(6)
                ->pluck('id')
                ->all(),
        );

        $this->curateHomeSection(
            'show_in_whey_festival',
            'whey_festival_sort_order',
            Product::query()
                ->where('is_active', true)
                ->whereHas('category', fn ($category) => $category->where('slug', 'whey-protein'))
                ->orderBy('price_cents')
                ->take(10)
                ->pluck('id')
                ->all(),
        );

        $this->curateHomeSection(
            'show_in_creatine_house',
            'creatine_house_sort_order',
            Product::query()
                ->where('is_active', true)
                ->whereHas('category', fn ($category) => $category->where('slug', 'creatina'))
                ->orderByDesc('sales_count')
                ->take(5)
                ->pluck('id')
                ->all(),
        );
    }

    private function curateHomeSection(string $flagColumn, string $sortColumn, array $productIds): void
    {
        foreach (array_values($productIds) as $index => $productId) {
            Product::query()
                ->whereKey($productId)
                ->update([
                    $flagColumn => true,
                    $sortColumn => ($index + 1) * 10,
                ]);
        }
    }

    private function bannerImagePath(array $bannerData, Product $product, int $index): ?string
    {
        $path = 'banners/imported/home-offer-'.($index + 1).'.webp';

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $width = 1600;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        [$startHex, $endHex] = $bannerData['theme'];
        $start = $this->rgb($startHex);
        $end = $this->rgb($endHex);

        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $color = imagecolorallocate(
                $image,
                (int) ($start[0] + (($end[0] - $start[0]) * $ratio)),
                (int) ($start[1] + (($end[1] - $start[1]) * $ratio)),
                (int) ($start[2] + (($end[2] - $start[2]) * $ratio)),
            );

            imageline($image, $x, 0, $x, $height, $color);
        }

        $productImagePath = Storage::disk('public')->path($product->image_path);
        $productImage = is_file($productImagePath) ? @imagecreatefromstring(file_get_contents($productImagePath)) : null;

        if ($productImage) {
            imagepalettetotruecolor($productImage);
            $sourceWidth = imagesx($productImage);
            $sourceHeight = imagesy($productImage);
            $targetHeight = 470;
            $targetWidth = (int) ($sourceWidth * ($targetHeight / $sourceHeight));
            $targetX = $width - $targetWidth - 120;
            $targetY = 70;

            imagecopyresampled($image, $productImage, $targetX, $targetY, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            imagedestroy($productImage);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 226, 232, 240);
        imagestring($image, 5, 90, 110, mb_strtoupper($bannerData['title']), $white);
        imagestring($image, 4, 90, 170, $this->latinText($bannerData['subtitle'], 86), $muted);
        imagestring($image, 5, 90, 250, mb_strtoupper($bannerData['cta']), $white);

        ob_start();
        imagewebp($image, null, 88);
        $body = ob_get_clean();
        imagedestroy($image);

        if (! $body) {
            return null;
        }

        Storage::disk('public')->put($path, $body);

        return $path;
    }

    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function latinText(string $text, int $limit): string
    {
        return Str::limit(Str::ascii($text), $limit, '');
    }

    private function manufacturerUrl(string $brand, string $name): ?string
    {
        $key = $brand.'|'.$name;

        if (array_key_exists($key, $this->manufacturerUrls)) {
            return $this->manufacturerUrls[$key];
        }

        $officialDomain = $this->officialDomain($brand);

        return $this->manufacturerUrls[$key] = $officialDomain ? 'https://'.$officialDomain : null;
    }

    private function officialDomain(string $brand): ?string
    {
        return match (Str::slug($brand)) {
            'body-action' => 'bodyaction.com.br',
            'central-nutrition' => 'centralnutrition.com.br',
            'darkness' => 'darkness.com.br',
            'dux' => 'duxnutrition.com.br',
            'integral-medica' => 'integralmedica.com.br',
            'max-titanio' => 'maxtitanium.com.br',
            'new-millen' => 'newmillen.com.br',
            'probiotica' => 'probiotica.com.br',
            'sudract' => 'sudract.com.br',
            'super-coffe' => 'supercoffee.com.br',
            'z2-performance' => 'z2performance.com',
            default => null,
        };
    }

    private function webUrls(string $query): array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://duckduckgo.com/html/', ['q' => $query]);

            if (! $response->successful()) {
                return [];
            }

            preg_match_all('/class="result__a" href="([^"]+)"/', $response->body(), $matches);

            return collect($matches[1] ?? [])
                ->map(fn (string $url): string => html_entity_decode($url))
                ->map(function (string $url): string {
                    if (str_contains($url, 'uddg=')) {
                        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

                        return urldecode((string) ($query['uddg'] ?? $url));
                    }

                    return $url;
                })
                ->filter(fn (string $url): bool => str_starts_with($url, 'http'))
                ->unique()
                ->take(5)
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function imagePath(string $brand, string $name, string $slug, bool $allowRemoteSearch = false): ?string
    {
        $basePath = "products/imported/{$slug}";
        $webpPath = "{$basePath}.webp";

        if (Storage::disk('public')->exists($webpPath)) {
            return $webpPath;
        }

        foreach (['jpg', 'jpeg', 'png'] as $extension) {
            $legacyPath = "{$basePath}.{$extension}";

            if (Storage::disk('public')->exists($legacyPath) && $this->storeWebpImage(Storage::disk('public')->get($legacyPath), $webpPath)) {
                return $webpPath;
            }
        }

        if ($matchedPath = $this->matchingImportedImagePath($slug, $webpPath)) {
            return $matchedPath;
        }

        if (! $allowRemoteSearch) {
            return null;
        }

        foreach ($this->imageUrls(trim("{$brand} {$name} suplemento")) as $imageUrl) {
            try {
                $response = Http::timeout(6)
                    ->retry(1, 150)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($imageUrl);

                if (! $response->successful()) {
                    continue;
                }

                $body = $response->body();

                if ($this->storeWebpImage($body, $webpPath)) {
                    return $webpPath;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function matchingImportedImagePath(string $slug, string $webpPath): ?string
    {
        $needle = preg_replace('/-\d{4}$/', '', $slug) ?: $slug;

        if (mb_strlen($needle) < 8) {
            return null;
        }

        foreach (Storage::disk('public')->files('products/imported') as $path) {
            if (! preg_match('/\.(?:webp|jpe?g|png)$/i', $path)) {
                continue;
            }

            $fileSlug = Str::of(pathinfo($path, PATHINFO_FILENAME))->lower()->toString();

            if (! str_contains($fileSlug, $needle)) {
                continue;
            }

            if (str_ends_with($path, '.webp')) {
                return $path;
            }

            if ($this->storeWebpImage(Storage::disk('public')->get($path), $webpPath)) {
                return $webpPath;
            }

            return $path;
        }

        return null;
    }

    private function imageUrls(string $query): array
    {
        try {
            $encodedQuery = rawurlencode($query);

            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get("https://duckduckgo.com/?q={$encodedQuery}&iax=images&ia=images");

            if (! $response->successful()) {
                return [];
            }

            if (! preg_match('/vqd=([\\d-]+)/', $response->body(), $matches)) {
                return [];
            }

            $imagesResponse = Http::timeout(8)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                    'Referer' => 'https://duckduckgo.com/',
                ])
                ->get('https://duckduckgo.com/i.js', [
                    'l' => 'br-pt',
                    'o' => 'json',
                    'q' => $query,
                    'vqd' => $matches[1],
                    'f' => ',,,',
                    'p' => 1,
                ]);

            if (! $imagesResponse->successful()) {
                return [];
            }

            return collect($imagesResponse->json('results', []))
                ->sortByDesc(fn (array $result): int => ((int) data_get($result, 'width', 0)) * ((int) data_get($result, 'height', 0)))
                ->flatMap(fn (array $result): array => [
                    data_get($result, 'image'),
                    data_get($result, 'thumbnail'),
                ])
                ->filter()
                ->map(fn (string $url): string => Str::of($url)->replace('http://', 'https://')->toString())
                ->unique()
                ->take(5)
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function storeWebpImage(string $body, string $path): bool
    {
        $image = @imagecreatefromstring($body);

        if (! $image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 360 || $height < 360) {
            imagedestroy($image);

            return false;
        }

        $maxDimension = 1600;

        if (max($width, $height) > $maxDimension) {
            $ratio = $maxDimension / max($width, $height);
            $targetWidth = (int) round($width * $ratio);
            $targetHeight = (int) round($height * $ratio);
            $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            imagedestroy($image);
            $image = $resizedImage;
        }

        ob_start();
        imagepalettetotruecolor($image);
        imagewebp($image, null, 86);
        $webp = ob_get_clean();
        imagedestroy($image);

        if (! $webp) {
            return false;
        }

        Storage::disk('public')->put($path, $webp);

        return true;
    }

    private function extensionFromResponse(?string $contentType, string $url): string
    {
        $contentType = Str::lower((string) $contentType);
        $url = Str::lower($url);

        if (str_contains($contentType, 'webp') || str_ends_with($url, '.webp')) {
            return 'webp';
        }

        if (str_contains($contentType, 'png') || str_ends_with($url, '.png')) {
            return 'png';
        }

        return 'jpg';
    }

    private function productSlug(string $brand, string $category, string $name, int $index): string
    {
        return Str::slug($brand.' '.$category.' '.$name).'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
    }

    private function sku(string $brand, string $category, string $name, ?string $variant = null): string
    {
        $hash = strtoupper(substr(md5($brand.'|'.$category.'|'.$name.'|'.$variant), 0, 8));

        return 'RS-'.$hash;
    }

    private function weight(string $name): ?string
    {
        preg_match('/\b\d+(?:[,.]\d+)?\s?(?:KG|G|ML|L|CAPS(?:ULAS)?|CÁPSULAS|SACHES|SACHE|STICKS)\b/iu', $name, $matches);

        return $matches[0] ?? null;
    }

    private function icon(string $name): string
    {
        return Str::of($name)
            ->replaceMatches('/[^A-Z0-9 ]/u', '')
            ->explode(' ')
            ->filter()
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->take(2)
            ->implode('') ?: 'RS';
    }

    private function categoryDescription(string $name): string
    {
        return match (Str::upper($name)) {
            'WHEY PROTEIN' => 'Proteínas para rotina de recuperação, ganho de massa e praticidade.',
            'CREATINA' => 'Creatinas para força, potência e performance diária.',
            'PRÉ-TREINO' => 'Fórmulas para energia, foco e intensidade no treino.',
            'VITAMINAS' => 'Vitaminas e compostos para suporte à saúde e bem-estar.',
            'ENERGIA' => 'Géis, bebidas e suplementos energéticos para treinos e provas.',
            default => 'Produtos selecionados para performance, saúde e rotina esportiva.',
        };
    }

    private function productContent(array $data, ?string $manufacturerUrl): array
    {
        $category = Str::upper($data['category']);
        $name = Str::upper($data['name']);
        $brand = $data['brand'];
        $family = $this->productFamily($category, $name);
        $flavors = collect($data['options'])->pluck('value')->filter()->implode(', ');

        return [
            'short_description' => $this->shortDescriptionFor($data, $family),
            'description' => $this->descriptionFor($data, $family, $flavors),
            'benefits' => $this->benefitsFor($family),
            'usage_instructions' => $this->usageFor($family),
            'ingredients' => $this->ingredientsFor($data, $family),
            'nutrition_facts' => $this->nutritionFor($data, $family),
            'serving_size' => $this->servingSizeFor($family),
            'allergen_info' => $this->allergenFor($family),
        ];
    }

    private function productFamily(string $category, string $name): string
    {
        return match (true) {
            str_contains($category, 'CREATINA') || str_contains($name, 'CREATINA') => 'creatine',
            str_contains($category, 'WHEY') || str_contains($name, 'WHEY') || str_contains($name, 'PROTEIN') => 'protein',
            str_contains($category, 'PRÉ') || str_contains($name, 'PRE WORKOUT') || str_contains($name, 'CAFEINA') => 'preworkout',
            str_contains($category, 'ENERGIA') || str_contains($name, 'GEL') || str_contains($name, 'ENERGY') => 'energy',
            str_contains($category, 'VITAMINAS') || str_contains($name, 'OMEGA') || str_contains($name, 'MAGNESIO') => 'wellness',
            str_contains($category, 'COLÁGENO') || str_contains($name, 'COLLAGEN') => 'collagen',
            str_contains($category, 'HIPER') || str_contains($name, 'MASS') => 'mass',
            default => 'general',
        };
    }

    private function webDescription(array $data): ?string
    {
        $key = $data['brand'].'|'.$data['name'];

        if (array_key_exists($key, $this->descriptionCache)) {
            return $this->descriptionCache[$key];
        }

        $queries = [
            $data['brand'].' '.$data['name'].' descrição suplemento',
            $data['brand'].' '.$data['name'].' Mercado Livre',
            $data['brand'].' '.$data['name'].' Amazon',
        ];

        foreach ($queries as $query) {
            $description = $this->searchDescription($query);

            if ($description) {
                return $this->descriptionCache[$key] = $description;
            }
        }

        return $this->descriptionCache[$key] = null;
    }

    private function searchDescription(string $query): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://duckduckgo.com/html/', ['q' => $query]);

            if (! $response->successful()) {
                return null;
            }

            preg_match_all('/class="result__snippet"[^>]*>(.*?)<\\/a>|class="result__snippet"[^>]*>(.*?)<\\/div>/s', $response->body(), $matches);

            return collect(array_merge($matches[1] ?? [], $matches[2] ?? []))
                ->map(fn (string $snippet): string => trim(strip_tags(html_entity_decode($snippet))))
                ->map(fn (string $snippet): string => preg_replace('/\\s+/', ' ', $snippet) ?: '')
                ->filter(fn (string $snippet): bool => mb_strlen($snippet) >= 120)
                ->reject(fn (string $snippet): bool => str_contains(Str::lower($snippet), 'frete') || str_contains(Str::lower($snippet), 'r$'))
                ->map(fn (string $snippet): string => Str::finish($snippet, '.'))
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function shortDescriptionFor(array $data, string $family): string
    {
        return match ($family) {
            'creatine' => "{$data['name']} da {$data['brand']} para rotina de força, potência e treinos intensos.",
            'protein' => "{$data['name']} da {$data['brand']} para complementar proteínas com praticidade.",
            'preworkout' => "{$data['name']} da {$data['brand']} para energia e foco antes do treino.",
            'energy' => "{$data['name']} da {$data['brand']} para reposição rápida em treinos e provas.",
            'wellness' => "{$data['name']} da {$data['brand']} para suporte diário de saúde e bem-estar.",
            'collagen' => "{$data['name']} da {$data['brand']} para complementar a ingestão de colágeno.",
            'mass' => "{$data['name']} da {$data['brand']} para dieta hipercalórica e ganho de massa.",
            default => "{$data['name']} da {$data['brand']} para completar sua rotina esportiva.",
        };
    }

    private function descriptionFor(array $data, string $family, string $flavors): string
    {
        $flavorSentence = $flavors ? " Disponível em {$flavors}." : '';

        return match ($family) {
            'creatine' => "{$data['name']} da {$data['brand']} é indicado para quem busca suporte nutricional em treinos de força, potência e alta intensidade. A fórmula privilegia creatina monohidratada e encaixa bem em rotinas diárias de performance.",
            'protein' => "{$data['name']} da {$data['brand']} é uma proteína prática para complementar a ingestão diária, preparar shakes e apoiar a recuperação pós-treino.{$flavorSentence} Uma boa escolha para quem quer praticidade sem abrir mão de uma rotina alimentar consistente.",
            'preworkout' => "{$data['name']} da {$data['brand']} reúne ativos voltados a disposição, foco e intensidade para ser usado antes do treino. É uma escolha para sessões em que energia e constância fazem diferença.",
            'energy' => "{$data['name']} da {$data['brand']} foi pensado para consumo prático durante treinos, provas ou rotinas com maior demanda energética.{$flavorSentence} O formato facilita o uso antes ou durante a atividade, especialmente quando praticidade importa.",
            'wellness' => "{$data['name']} da {$data['brand']} complementa a rotina diária com nutrientes e compostos específicos para saúde, imunidade, equilíbrio ou recuperação, de acordo com a proposta da fórmula.",
            'collagen' => "{$data['name']} da {$data['brand']} é uma opção para suplementar colágeno na rotina alimentar, com uso simples e proposta voltada a consistência diária.{$flavorSentence}",
            'mass' => "{$data['name']} da {$data['brand']} é um hipercalórico para dietas que precisam de maior aporte energético e praticidade no preparo de shakes.{$flavorSentence}",
            default => "{$data['name']} da {$data['brand']} atende rotinas esportivas e de suplementação com uso prático no dia a dia.",
        };
    }

    private function benefitsFor(string $family): array
    {
        return match ($family) {
            'creatine' => ['Força e potência', 'Uso diário simples', 'Alta aderência à rotina', 'Retirada disponível'],
            'protein' => ['Complemento proteico', 'Recuperação muscular', 'Preparo rápido', 'Variedade de sabores'],
            'preworkout' => ['Energia antes do treino', 'Foco e disposição', 'Rotina de alta intensidade', 'Variações controladas'],
            'energy' => ['Reposição prática', 'Uso em treino ou prova', 'Formato fácil de carregar', 'Sabores disponíveis'],
            'wellness' => ['Suporte diário', 'Compostos específicos', 'Praticidade na rotina', 'Entrega local'],
            'collagen' => ['Suplementação de colágeno', 'Uso diário', 'Fácil preparo', 'Sabores disponíveis'],
            'mass' => ['Maior aporte calórico', 'Preparo em shake', 'Apoio à dieta de ganho', 'Sabores disponíveis'],
            default => ['Praticidade', 'Compra rápida', 'Retirada na loja', 'Entrega local'],
        };
    }

    private function usageFor(string $family): string
    {
        return match ($family) {
            'creatine' => 'Misture a porção em água, suco ou bebida de preferência e consuma diariamente, de preferência em horário fixo.',
            'protein', 'mass', 'collagen' => 'Misture a porção em água, leite ou bebida de preferência até diluir. Ajuste o consumo ao seu plano alimentar.',
            'preworkout' => 'Consuma antes do treino, respeitando tolerância individual a estimulantes e cafeína.',
            'energy' => 'Use antes ou durante a atividade, conforme duração e intensidade do treino ou prova.',
            'wellness' => 'Consuma junto à rotina diária, respeitando a dose indicada no rótulo do produto.',
            default => 'Consuma de acordo com a proposta do produto e com orientação profissional quando necessário.',
        };
    }

    private function ingredientsFor(array $data, string $family): string
    {
        return match ($family) {
            'creatine' => str_contains(Str::upper($data['name']), 'CREAPURE') ? 'Creatina monohidratada Creapure.' : 'Creatina monohidratada.',
            'protein' => 'Proteínas do leite, aromatizantes e edulcorantes. A composição pode mudar entre sabores.',
            'preworkout' => 'Blend de aminoácidos, estimulantes e compostos energéticos de acordo com a fórmula do produto.',
            'energy' => 'Carboidratos, eletrólitos, aromatizantes e outros ativos energéticos conforme a versão.',
            'wellness' => 'Vitaminas, minerais, ácidos graxos ou compostos ativos conforme a proposta do produto.',
            'collagen' => 'Colágeno, aromatizantes e ingredientes complementares conforme a versão.',
            'mass' => 'Carboidratos, proteínas, aromatizantes e ingredientes energéticos para preparo de shake.',
            default => 'Composição específica da fórmula comercializada.',
        };
    }

    private function nutritionFor(array $data, string $family): ?array
    {
        if ($family === 'energy' && preg_match('/\b(40|45|50)\s*CARB\b/i', $data['name'], $matches)) {
            return [
                'Carboidratos' => $matches[1].' g',
                'Uso sugerido' => 'Energia para treinos longos',
            ];
        }

        return match ($family) {
            'creatine' => ['Valor energético' => '0 kcal', 'Creatina' => '3 g'],
            'protein' => ['Proteínas' => '20 g a 27 g', 'Carboidratos' => '1 g a 12 g', 'Gorduras totais' => '0 g a 4 g'],
            'preworkout' => ['Cafeína' => 'Presente em versões estimulantes', 'Aminoácidos' => 'Blend da fórmula', 'Valor energético' => 'Baixo por porção'],
            'energy' => ['Carboidratos' => 'Fonte energética principal', 'Sódio' => 'Presente em versões de reposição', 'Valor energético' => 'De acordo com porção'],
            'collagen' => ['Colágeno' => 'Principal ativo da fórmula', 'Proteínas' => 'Presente por porção'],
            'mass' => ['Carboidratos' => 'Alto teor', 'Proteínas' => 'Presente por porção', 'Valor energético' => 'Alto teor calórico'],
            default => null,
        };
    }

    private function servingSizeFor(string $family): ?string
    {
        return match ($family) {
            'creatine' => '3 g',
            'protein' => '30 g a 40 g',
            'preworkout' => '1 dosador',
            'energy' => '1 unidade ou porção',
            'collagen' => '1 dosador',
            'mass' => 'Porção para shake',
            default => null,
        };
    }

    private function allergenFor(string $family): string
    {
        return match ($family) {
            'protein', 'mass' => 'Contém derivados do leite e pode conter soja ou glúten, dependendo do sabor.',
            'preworkout', 'energy' => 'Pode conter cafeína, corantes, edulcorantes e traços de alergênicos da linha de produção.',
            'collagen' => 'Pode conter aromatizantes, edulcorantes e alergênicos da linha de produção.',
            'wellness' => 'Confira cápsula, excipientes e possíveis alergênicos da fórmula.',
            default => 'Verifique alergênicos destacados no rótulo antes do consumo.',
        };
    }
}
