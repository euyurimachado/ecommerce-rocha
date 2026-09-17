<?php

namespace App\Filament\Resources\StoreSettings;

use App\Filament\Resources\StoreSettings\Pages\EditStoreSetting;
use App\Filament\Resources\StoreSettings\Pages\ListStoreSettings;
use App\Models\StoreSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreSettingResource extends Resource
{
    protected static ?string $model = StoreSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Loja e aparência';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Configuração da loja';

    protected static ?string $pluralModelLabel = 'Loja, aparência e empresa';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Loja')->columns(2)->schema([
                TextInput::make('name')->label('Nome da loja')->required()->maxLength(120),
                TextInput::make('short_name')->label('Nome curto')->required()->maxLength(40),
                TextInput::make('legal_name')->label('Razão social')->maxLength(160),
                TextInput::make('slogan')->label('Slogan')->maxLength(160),
            ]),
            Section::make('Aparência')->columns(2)->schema([
                FileUpload::make('logo_path')->label('Logo principal')->image()->disk('public')->directory('branding'),
                FileUpload::make('logo_dark_path')->label('Logo alternativa')->image()->disk('public')->directory('branding'),
                FileUpload::make('favicon_path')->label('Favicon')->image()->disk('public')->directory('branding'),
                FileUpload::make('pwa_icon_path')->label('Ícone PWA')->image()->disk('public')->directory('branding'),
                TextInput::make('primary_color')->label('Cor primária')->type('color')->regex('/^#[0-9A-Fa-f]{6}$/')->required(),
                TextInput::make('primary_dark_color')->label('Cor primária escura')->type('color')->regex('/^#[0-9A-Fa-f]{6}$/')->required(),
                TextInput::make('secondary_color')->label('Cor secundária')->type('color')->regex('/^#[0-9A-Fa-f]{6}$/')->required(),
                TextInput::make('accent_color')->label('Cor de destaque')->type('color')->regex('/^#[0-9A-Fa-f]{6}$/')->required(),
                TextInput::make('background_color')->label('Cor de fundo')->type('color')->regex('/^#[0-9A-Fa-f]{6}$/')->required(),
                Select::make('font_family')->label('Fonte principal')->options(array_combine(StoreSetting::FONTS, StoreSetting::FONTS))->required(),
            ]),
            Section::make('Empresa')->columns(3)->schema([
                TextInput::make('tax_id')->label('CPF/CNPJ'),
                TextInput::make('state_registration')->label('Inscrição estadual'),
                TextInput::make('email')->label('E-mail')->email(),
                TextInput::make('phone')->label('Telefone'),
                TextInput::make('whatsapp')->label('WhatsApp'),
                TextInput::make('postal_code')->label('CEP'),
                TextInput::make('street')->label('Rua')->columnSpan(2),
                TextInput::make('number')->label('Número'),
                TextInput::make('complement')->label('Complemento'),
                TextInput::make('neighborhood')->label('Bairro'),
                TextInput::make('city')->label('Cidade'),
                TextInput::make('state')->label('UF')->maxLength(2),
                TextInput::make('country')->label('País')->default('BR')->maxLength(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Loja'),
            TextColumn::make('legal_name')->label('Razão social'),
            TextColumn::make('installed_at')->label('Instalada em')->dateTime(),
            TextColumn::make('installation_version')->label('Versão'),
        ])->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return ! StoreSetting::query()->exists();
    }

    public static function getPages(): array
    {
        return ['index' => ListStoreSettings::route('/'), 'edit' => EditStoreSetting::route('/{record}/edit')];
    }
}
