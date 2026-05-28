<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Whey Protein', 'icon' => 'WP', 'short_description' => 'Proteinas para ganho de massa e recuperacao.', 'sort_order' => 10],
            ['name' => 'Creatina', 'icon' => 'CR', 'short_description' => 'Forca, potencia e performance diaria.', 'sort_order' => 20],
            ['name' => 'Pre-treino', 'icon' => 'PT', 'short_description' => 'Energia e foco para treinos intensos.', 'sort_order' => 30],
            ['name' => 'Vitaminas', 'icon' => 'VT', 'short_description' => 'Saude, imunidade e rotina equilibrada.', 'sort_order' => 40],
            ['name' => 'Snacks', 'icon' => 'SN', 'short_description' => 'Lanches praticos para qualquer hora.', 'sort_order' => 50],
            ['name' => 'Acessorios', 'icon' => 'AC', 'short_description' => 'Shakers, coqueteleiras e acessorios.', 'sort_order' => 60],
            ['name' => 'Combos e Kits', 'icon' => 'CK', 'short_description' => 'Selecoes prontas para objetivos especificos.', 'sort_order' => 70],
        ])->mapWithKeys(function (array $data) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    ...$data,
                    'seo_description' => $data['name'].' com entrega rapida em Campos dos Goytacazes, RJ. Produtos originais e atendimento especializado da Rocha Sports.',
                    'meta_title' => $data['name'].' em Campos dos Goytacazes | Rocha Sports',
                    'meta_description' => 'Compre '.$data['name'].' original com entrega rapida ou retirada na Rocha Sports em Campos dos Goytacazes.',
                    'is_active' => true,
                    'is_featured' => true,
                ],
            );

            return [$category->name => $category];
        });

        $brands = collect(['Max Titanium', 'Integralmedica', 'Growth Supplements', 'Dux Nutrition', 'Probiotica'])
            ->mapWithKeys(function (string $name) {
                $brand = Brand::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'name' => $name,
                        'description' => 'Marca parceira com produtos selecionados pela curadoria Rocha Sports.',
                        'is_active' => true,
                        'is_featured' => true,
                    ],
                );

                return [$brand->name => $brand];
            });

        $products = [
            ['name' => 'Whey Protein 100% Concentrado 900g', 'category' => 'Whey Protein', 'brand' => 'Max Titanium', 'weight' => '900g', 'flavor' => 'Chocolate', 'price_cents' => 12990, 'compare_at_price_cents' => 14990, 'rating' => 4.8, 'reviews_count' => 42, 'sales_count' => 180, 'is_featured' => true, 'is_offer' => true],
            ['name' => 'Creatina Monohidratada 300g', 'category' => 'Creatina', 'brand' => 'Integralmedica', 'weight' => '300g', 'flavor' => 'Sem sabor', 'price_cents' => 8990, 'compare_at_price_cents' => 9990, 'rating' => 4.9, 'reviews_count' => 68, 'sales_count' => 230, 'is_featured' => true, 'is_offer' => true],
            ['name' => 'Pre-treino Energy Focus 300g', 'category' => 'Pre-treino', 'brand' => 'Dux Nutrition', 'weight' => '300g', 'flavor' => 'Blue Ice', 'price_cents' => 11990, 'rating' => 4.7, 'reviews_count' => 31, 'sales_count' => 96, 'is_featured' => true, 'is_offer' => false],
            ['name' => 'Multivitaminico Performance 120 caps', 'category' => 'Vitaminas', 'brand' => 'Growth Supplements', 'weight' => '120 caps', 'flavor' => null, 'price_cents' => 5490, 'rating' => 4.6, 'reviews_count' => 19, 'sales_count' => 77, 'is_featured' => true, 'is_offer' => false],
            ['name' => 'Barra Proteica Cookies 12 unidades', 'category' => 'Snacks', 'brand' => 'Probiotica', 'weight' => '12 un', 'flavor' => 'Cookies', 'price_cents' => 6990, 'compare_at_price_cents' => 7990, 'rating' => 4.5, 'reviews_count' => 14, 'sales_count' => 61, 'is_featured' => false, 'is_offer' => true],
            ['name' => 'Combo Massa: Whey + Creatina + Coqueteleira', 'category' => 'Combos e Kits', 'brand' => 'Max Titanium', 'weight' => 'Kit', 'flavor' => 'Chocolate', 'price_cents' => 21990, 'compare_at_price_cents' => 24990, 'rating' => 4.9, 'reviews_count' => 23, 'sales_count' => 88, 'is_featured' => true, 'is_offer' => true],
        ];

        foreach ($products as $index => $data) {
            Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id' => $categories[$data['category']]->id,
                    'brand_id' => $brands[$data['brand']]->id,
                    'name' => $data['name'],
                    'sku' => 'RS-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'weight' => $data['weight'],
                    'flavor' => $data['flavor'],
                    'short_description' => 'Produto original com curadoria Rocha Sports, ideal para rotina de performance.',
                    'description' => 'Selecionado para atletas e praticantes de atividade fisica que buscam praticidade, resultado e compra segura em Campos dos Goytacazes.',
                    'benefits' => ['Produto original', 'Entrega local rapida', 'Retirada disponivel na loja', 'Atendimento especializado'],
                    'usage_instructions' => 'Consulte a recomendacao do fabricante no rotulo do produto.',
                    'ingredients' => 'Confira a tabela nutricional e ingredientes no rotulo do produto.',
                    'stock_quantity' => 24 + ($index * 3),
                    'reserved_quantity' => 0,
                    'price_cents' => $data['price_cents'],
                    'compare_at_price_cents' => $data['compare_at_price_cents'] ?? null,
                    'rating' => $data['rating'],
                    'reviews_count' => $data['reviews_count'],
                    'sales_count' => $data['sales_count'],
                    'is_active' => true,
                    'is_featured' => $data['is_featured'],
                    'is_offer' => $data['is_offer'],
                    'allows_pickup' => true,
                    'allows_local_delivery' => true,
                    'meta_title' => $data['name'].' | Rocha Sports',
                    'meta_description' => 'Compre '.$data['name'].' com entrega rapida em Campos dos Goytacazes ou retirada na Rocha Sports.',
                ],
            );
        }

        Banner::updateOrCreate(
            ['placement' => 'home_hero', 'sort_order' => 1],
            [
                'title' => 'Suplementos originais com entrega rapida em Campos',
                'subtitle' => 'Whey, creatina, pre-treino e combos selecionados por especialistas Rocha Sports.',
                'cta_label' => 'Ver ofertas',
                'url' => '/ofertas',
                'device' => 'all',
                'is_active' => true,
            ],
        );
    }
}
