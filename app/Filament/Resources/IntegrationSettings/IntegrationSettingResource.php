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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

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
                    : ['mercado_pago' => 'Mercado Pago', 'asaas' => 'Asaas'])
                    ->required()->live()
                    ->unique(
                        table: 'integration_settings',
                        column: 'provider',
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('type', $get('type')),
                    ),
                Select::make('environment')->label('Ambiente')->options(['sandbox' => 'Sandbox', 'production' => 'Produção'])->required(),
                Toggle::make('enabled')->label('Ativa')->helperText('Somente uma integração por área deve permanecer ativa.'),
            ]),
            Section::make('Rotacionar credenciais')->description('Campos vazios preservam os valores atuais. Segredos existentes nunca são preenchidos no navegador.')->columns(2)->schema([
                TextInput::make('credential_public_key')->label('Public Key')->helperText(fn (?IntegrationSetting $record): string => filled($record?->credential('public_key')) ? 'Configurada. Deixe em branco para manter a Public Key atual.' : 'Não configurada. Informe a Public Key para configurar o provider.')
                    ->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('credential_access_token')->label('Novo Access Token')->password()
                    ->helperText(fn (?IntegrationSetting $record): string => filled($record?->credential('access_token')) ? 'Configurado. Deixe em branco para manter o Access Token atual.' : 'Não configurado. Informe um Access Token para configurar o provider.')
                    ->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('credential_webhook_secret')->label('Nova assinatura secreta do Webhook')->password()->helperText(fn (?IntegrationSetting $record): string => filled($record?->credential('webhook_secret')) ? 'Configurada. Deixe em branco para manter a assinatura atual. Use a assinatura secreta gerada pelo Mercado Pago em Webhooks > Configurar notificação. Não utilize o Client Secret da aplicação.' : 'Não configurada. Use a assinatura secreta gerada pelo Mercado Pago em Webhooks > Configurar notificação. Não utilize o Client Secret da aplicação.')->visible(fn (Get $get) => $get('provider') === 'mercado_pago'),
                TextInput::make('credential_api_key')->label('Nova API key')->password()
                    ->helperText(fn (?IntegrationSetting $record): string => filled($record?->credential('api_key')) ? 'Configurada. Deixe em branco para manter a API key atual.' : 'Não configurada. Informe a API key para configurar o provider.')
                    ->visible(fn (Get $get) => $get('provider') === 'asaas'),
                TextInput::make('credential_webhook_token')->label('Novo token do webhook')->password()
                    ->helperText(fn (?IntegrationSetting $record): string => filled($record?->credential('webhook_token')) ? 'Configurado. Deixe em branco para manter o token atual.' : 'Não configurado. Informe o token do webhook para configurar o provider.')
                    ->visible(fn (Get $get) => $get('provider') === 'asaas'),
                TextInput::make('credential_client_id')->label('Client ID')->visible(fn (Get $get) => $get('provider') === 'melhor_envio'),
                TextInput::make('credential_client_secret')->label('Novo Client Secret')->password()
                    ->helperText(fn (?IntegrationSetting $record): string => filled($record?->credential('client_secret')) ? 'Configurado. Deixe em branco para manter o Client Secret atual.' : 'Não configurado. Informe o Client Secret para configurar o provider.')
                    ->visible(fn (Get $get) => $get('provider') === 'melhor_envio'),
            ]),
            Section::make('Taxa fixa')->columns(2)->visible(fn (Get $get) => $get('provider') === 'flat_rate')->schema([
                TextInput::make('settings.name')->label('Nome exibido')->default('Entrega local'),
                TextInput::make('settings.price_cents')->label('Valor em centavos')->numeric()->minValue(0),
                TextInput::make('settings.min_days')->label('Prazo mínimo')->numeric()->minValue(0),
                TextInput::make('settings.max_days')->label('Prazo máximo')->numeric()->minValue(0),
                TextInput::make('settings.free_shipping_threshold_cents')->label('Grátis acima de')->numeric()->minValue(0),
            ]),
            Section::make('URLs da integração')->schema([
                TextInput::make('_mercado_pago_webhook')->label('URL do Webhook')->helperText('Cadastre esta URL na configuração de Webhooks da aplicação no Mercado Pago.')
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
            TextColumn::make('enabled')->label('Status')->formatStateUsing(fn (bool $state): string => $state ? 'Ativa' : 'Desativada')
                ->badge()->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            TextColumn::make('configuration_status')->label('Credenciais')->getStateUsing(function (IntegrationSetting $record): string {
                $required = match ([$record->type, $record->provider]) {
                    ['payment', 'mercado_pago'] => ['access_token', 'public_key', 'webhook_secret'],
                    ['payment', 'asaas'] => ['api_key', 'webhook_token'],
                    ['shipping', 'melhor_envio'] => ['client_id', 'client_secret'],
                    ['shipping', 'flat_rate'] => [],
                    default => [],
                };
                $configured = $required === []
                    ? ($record->provider === 'flat_rate' && filled($record->setting('name')))
                    : collect($required)->every(fn (string $key): bool => filled($record->credential($key)));

                return $configured ? 'Configurada' : 'Não configurada';
            })->badge()->color(fn (string $state): string => $state === 'Configurada' ? 'success' : 'warning'),
            TextColumn::make('environment')->label('Ambiente')->badge(),
            TextColumn::make('last_test_status')->label('Teste')->formatStateUsing(fn (?string $state, IntegrationSetting $record): string => match (true) {
                $state === 'failed' => 'Teste falhou',
                $state === 'connected' && $record->connected_at !== null => 'Conexão testada',
                $state === 'disconnected' => 'Desconectada',
                default => 'Não testada',
            })->badge(),
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
