<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        $store = StoreSetting::current();

        return response()
            ->json([
                'name' => $store->name,
                'short_name' => $store->short_name,
                'description' => $store->slogan,
                'start_url' => '/',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait-primary',
                'background_color' => $store->background_color,
                'theme_color' => $store->primary_color,
                'lang' => 'pt-BR',
                'dir' => 'ltr',
                'categories' => ['shopping', 'health', 'sports'],
                'icons' => [
                    [
                        'src' => $store->assetUrl('pwa_icon_path', 'images/pwa-icon.svg'),
                        'sizes' => 'any',
                        'type' => $store->assetMime('pwa_icon_path'),
                        'purpose' => 'any maskable',
                    ],
                    [
                        'src' => $store->assetUrl('logo_path', 'images/logo-rocha-sports.webp'),
                        'sizes' => '1024x300',
                        'type' => $store->assetMime('logo_path', 'image/webp'),
                        'purpose' => 'any',
                    ],
                ],
                'shortcuts' => [
                    [
                        'name' => 'Buscar suplementos',
                        'short_name' => 'Busca',
                        'description' => 'Encontrar produtos, marcas e categorias.',
                        'url' => '/buscar',
                    ],
                    [
                        'name' => 'Ver ofertas',
                        'short_name' => 'Ofertas',
                        'description' => 'Abrir ofertas e combos disponíveis.',
                        'url' => '/buscar?ordenar=ofertas',
                    ],
                    [
                        'name' => 'Carrinho',
                        'short_name' => 'Carrinho',
                        'description' => 'Continuar sua compra.',
                        'url' => '/carrinho',
                    ],
                ],
            ], 200, [
                'Content-Type' => 'application/manifest+json',
            ]);
    }
}
