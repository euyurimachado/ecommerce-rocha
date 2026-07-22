<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\CurrencyInput;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(),
                Section::make('Imagens')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Imagem destacada')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->fetchFileInformation(false),
                        FileUpload::make('gallery_images')
                            ->label('Galeria de imagens')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('products/gallery')
                            ->visibility('public')
                            ->fetchFileInformation(false),
                    ])
                    ->columnSpanFull(),
                TextInput::make('weight')
                    ->label('Peso/volume'),
                Repeater::make('variations')
                    ->label('Variações do produto')
                    ->schema([
                        Select::make('name')
                            ->label('Nome da variação')
                            ->placeholder('Sabor, Cor, Tamanho...')
                            ->options(fn (): array => ProductVariation::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nome da variação')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => ProductVariation::findOrCreateByName($data['name'])->name)
                            ->required(),
                        Repeater::make('options')
                            ->label('Opções')
                            ->schema([
                                Select::make('value')
                                    ->label('Nome da opção')
                                    ->placeholder('Baunilha, Azul, M...')
                                    ->options(fn (Get $get): array => self::variationOptionChoices($get))
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('value')
                                            ->label('Nome da opção')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(fn (array $data): string => trim((string) $data['value']))
                                    ->required(),
                                ViewField::make('image_path')
                                    ->label('Imagem da opção')
                                    ->view('filament.forms.product-variation-image-picker')
                                    ->viewData(fn (Get $get): array => [
                                        'images' => self::productImageLibrary($get),
                                    ]),
                                FileUpload::make('uploaded_image_path')
                                    ->label('Anexar nova imagem')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/gallery')
                                    ->visibility('public')
                                    ->fetchFileInformation(false),
                                TextInput::make('sku')
                                    ->label('SKU da opção')
                                    ->maxLength(255),
                                CurrencyInput::make('price_cents')
                                    ->label('Preço da opção')
                                    ->helperText('Use vírgula para os centavos. Deixe vazio para usar o preço principal.'),
                                CurrencyInput::make('compare_at_price_cents')
                                    ->label('Preço anterior da opção'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Nova opção')
                            ->reorderable()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel('Nova variação')
                    ->reorderable()
                    ->columnSpanFull(),
                Textarea::make('short_description')
                    ->label('Descrição curta')
                    ->columnSpanFull(),
                RichEditor::make('description')
                    ->label('Descrição completa')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('products/descriptions')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
                TagsInput::make('benefits')
                    ->label('Benefícios')
                    ->columnSpanFull(),
                Textarea::make('usage_instructions')
                    ->label('Modo de uso')
                    ->columnSpanFull(),
                Textarea::make('ingredients')
                    ->label('Ingredientes')
                    ->columnSpanFull(),
                KeyValue::make('nutrition_facts')
                    ->label('Tabela nutricional')
                    ->keyLabel('Nutriente')
                    ->valueLabel('Quantidade')
                    ->columnSpanFull(),
                TextInput::make('serving_size')
                    ->label('Porção'),
                Textarea::make('allergen_info')
                    ->label('Informações de alergênicos')
                    ->columnSpanFull(),
                TextInput::make('manufacturer_url')
                    ->label('URL do fabricante'),
                TextInput::make('image_source_url')
                    ->label('Fonte da imagem'),
                CurrencyInput::make('price_cents')
                    ->label('Preço')
                    ->helperText('Use vírgula para os centavos, por exemplo 129,90.')
                    ->required(),
                CurrencyInput::make('compare_at_price_cents')
                    ->label('Preço anterior'),
                TextInput::make('rating')
                    ->label('Avaliação')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reviews_count')
                    ->label('Avaliações')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sales_count')
                    ->label('Vendas')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->required(),
                Toggle::make('is_featured')
                    ->label('Destaque')
                    ->required(),
                Toggle::make('is_offer')
                    ->label('Oferta')
                    ->required(),
                Section::make('Vitrine da home')
                    ->description('Escolha manualmente em quais seções este produto aparece. Se uma seção não tiver produtos escolhidos, a home usa a seleção automática.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_in_weight_loss')
                            ->label('Para emagrecer'),
                        TextInput::make('weight_loss_sort_order')
                            ->label('Ordem em Para emagrecer')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('show_in_energy')
                            ->label('Para ter energia'),
                        TextInput::make('energy_sort_order')
                            ->label('Ordem em Para ter energia')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('show_in_mass_gain')
                            ->label('Para ganhar massa'),
                        TextInput::make('mass_gain_sort_order')
                            ->label('Ordem em Para ganhar massa')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('show_in_whey_festival')
                            ->label('Festival Whey Protein'),
                        TextInput::make('whey_festival_sort_order')
                            ->label('Ordem no Festival Whey Protein')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('show_in_creatine_house')
                            ->label('Casa da creatina'),
                        TextInput::make('creatine_house_sort_order')
                            ->label('Ordem na Casa da creatina')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columnSpanFull(),
                Toggle::make('allows_pickup')
                    ->label('Permite retirada')
                    ->required(),
                Toggle::make('allows_local_delivery')
                    ->label('Permite entrega local')
                    ->required(),
                TextInput::make('meta_title')
                    ->label('Meta title'),
                TextInput::make('meta_description')
                    ->label('Meta description'),
            ]);
    }

    private static function variationOptionChoices(Get $get): array
    {
        $variationName = self::selectedVariationName($get);

        if ($variationName === '') {
            return [];
        }

        return ProductVariationOption::query()
            ->whereHas('variation', fn ($query) => $query
                ->where('normalized_name', ProductVariation::normalize($variationName))
                ->where('is_active', true))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('value')
            ->pluck('value', 'value')
            ->all();
    }

    private static function selectedVariationName(Get $get): string
    {
        return trim((string) $get('../../name'));
    }

    private static function productImageLibrary(Get $get): array
    {
        $currentProductImages = collect([$get('image_path', true)])
            ->flatten()
            ->merge(collect($get('gallery_images', true) ?? [])->flatten())
            ->filter();

        $savedProductImages = Product::query()
            ->select(['name', 'image_path', 'gallery_images'])
            ->latest('updated_at')
            ->get()
            ->flatMap(function (Product $product): array {
                return collect([$product->image_path])
                    ->merge($product->gallery_images ?? [])
                    ->filter()
                    ->map(fn (string $path): array => [
                        'path' => $path,
                        'label' => $product->name,
                    ])
                    ->all();
            });

        return $currentProductImages
            ->map(fn (string $path): array => [
                'path' => $path,
                'label' => 'Imagem deste produto',
            ])
            ->merge($savedProductImages)
            ->unique('path')
            ->map(fn (array $image): array => [
                'path' => $image['path'],
                'url' => asset('storage/'.$image['path']),
                'label' => $image['label'],
                'filename' => basename($image['path']),
            ])
            ->values()
            ->all();
    }
}
