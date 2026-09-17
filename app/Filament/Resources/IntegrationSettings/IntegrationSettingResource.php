<?php

namespace App\Filament\Resources\IntegrationSettings;

use App\Filament\Resources\IntegrationSettings\Pages\CreateIntegrationSetting;
use App\Filament\Resources\IntegrationSettings\Pages\EditIntegrationSetting;
use App\Filament\Resources\IntegrationSettings\Pages\ListIntegrationSettings;
use App\Models\IntegrationSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IntegrationSettingResource extends Resource
{
    protected static ?string $model = IntegrationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Pagamentos e entregas';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Integração';

    protected static ?string $pluralModelLabel = 'Integrações';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Integração')->columns(2)->schema([
                Select::make('type')->label('Área')->options(['payment' => 'Pagamentos', 'shipping' => 'Entregas'])->required()->live(),
                Select::make('provider')->label('Provider')->options(fn (Get $get): array => $get('type') === 'shipping'
                    ? ['flat_rate' => 'Taxa fixa', 'melhor_envio' => 'Melhor Envio']
                    : ['mercado_pago' => 'Mercado Pago', 'asaas' => 'Asaas'])->required()->live(),
                Select::make('environment')->label('Ambiente')->options(['sandbox' => 'Sandbox', 'production' => 'Produção'])->required(),
                Toggle::make('enabled')->label('Ativa')->helperText('Somente uma integração por área deve permanecer ativa.'),
            ]),
            Section::make('Rotacionar credenciais')->description('Campos vazios preservam os valores atuais. Segredos existentes nunca são preenchidos no navegador.')->columns(2)->schema([
                TextInput::make('credential_public_key')->label('Public Key')->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('credential_access_token')->label('Novo Access Token')->password()->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('credential_webhook_secret')->label('Novo webhook secret')->password()->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('credential_api_key')->label('Nova API key')->password()->visible(fn (Get $get) => $get('provider') === 'asaas'),
                TextInput::make('credential_webhook_token')->label('Novo token do webhook')->password()->visible(fn (Get $get) => $get('provider') === 'asaas'),
                TextInput::make('credential_client_id')->label('Client ID')->visible(fn (Get $get) => $get('provider') === 'melhor_envio'),
                TextInput::make('credential_client_secret')->label('Novo Client Secret')->password()->visible(fn (Get $get) => $get('provider') === 'melhor_envio'),
            ]),
            Section::make('Taxa fixa')->columns(2)->visible(fn (Get $get) => $get('provider') === 'flat_rate')->schema([
                TextInput::make('settings.name')->label('Nome exibido')->default('Entrega local'),
                TextInput::make('settings.price_cents')->label('Valor em centavos')->numeric()->minValue(0),
                TextInput::make('settings.min_days')->label('Prazo mínimo')->numeric()->minValue(0),
                TextInput::make('settings.max_days')->label('Prazo máximo')->numeric()->minValue(0),
                TextInput::make('settings.free_shipping_threshold_cents')->label('Grátis acima de')->numeric()->minValue(0),
            ]),
            Section::make('URLs da integração')->schema([
                TextInput::make('_mercado_pago_webhook')->label('Webhook Mercado Pago')
                    ->default(fn (): string => route('webhooks.payments', ['provider' => 'mercado-pago']))
                    ->disabled()->dehydrated(false)->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('_asaas_webhook')->label('Webhook Asaas')
                    ->default(fn (): string => route('webhooks.payments', ['provider' => 'asaas']))
                    ->disabled()->dehydrated(false)->visible(fn (Get $get) => $get('provider') === 'asaas'),
                TextInput::make('_melhor_envio_callback')->label('Callback OAuth Melhor Envio')
                    ->default(fn (): string => route('integrations.melhor-envio.callback'))
                    ->disabled()->dehydrated(false)->visible(fn (Get $get) => $get('provider') === 'melhor_envio'),
                TextInput::make('_melhor_envio_webhook')->label('Webhook Melhor Envio')
                    ->default(fn (): string => route('webhooks.shipping.melhor-envio'))
                    ->disabled()->dehydrated(false)->visible(fn (Get $get) => $get('provider') === 'melhor_envio'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('provider')->label('Provider')->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
            TextColumn::make('type')->label('Área')->badge(),
            IconColumn::make('enabled')->label('Ativa')->boolean(),
            TextColumn::make('environment')->label('Ambiente')->badge(),
            TextColumn::make('last_test_status')->label('Status')->placeholder('Não testada')->badge(),
            TextColumn::make('last_tested_at')->label('Último teste')->since()->placeholder('-'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntegrationSettings::route('/'),
            'create' => CreateIntegrationSetting::route('/create'),
            'edit' => EditIntegrationSetting::route('/{record}/edit'),
        ];
    }
}
