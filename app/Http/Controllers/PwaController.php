<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()
            ->json([
                'name' => 'Rocha Sports',
                'short_name' => 'Rocha',
                'description' => 'Suplementos originais com entrega rápida em Campos dos Goytacazes.',
                'start_url' => '/',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait-primary',
                'background_color' => '#ffffff',
                'theme_color' => '#0098d7',
                'lang' => 'pt-BR',
                'dir' => 'ltr',
                'categories' => ['shopping', 'health', 'sports'],
                'icons' => [
                    [
                        'src' => asset('images/pwa-icon.svg'),
                        'sizes' => 'any',
                        'type' => 'image/svg+xml',
                        'purpose' => 'any maskable',
                    ],
                    [
                        'src' => asset('images/logo-rocha-sports.webp'),
                        'sizes' => '1024x300',
                        'type' => 'image/webp',
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
