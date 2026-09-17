<?php

namespace App\Filament\Resources\IntegrationSettings\Pages;

use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\ShippingProviderManager;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationSetting extends EditRecord
{
    protected static string $resource = IntegrationSettingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $credentials = $this->record->credentials ?? [];
        foreach (['public_key', 'access_token', 'webhook_secret', 'api_key', 'webhook_token', 'client_id', 'client_secret'] as $key) {
            if (filled($data['credential_'.$key] ?? null)) {
                $credentials[$key] = $data['credential_'.$key];
            }
            unset($data['credential_'.$key]);
        }
        $data['credentials'] = $credentials;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectMelhorEnvio')
                ->label('Conectar Melhor Envio')
                ->icon('heroicon-o-link')
                ->visible(fn (): bool => $this->record->provider === 'melhor_envio')
                ->url(route('integrations.melhor-envio.redirect')),
            Action::make('test')->label('Testar conexão')->action(function (): void {
                $driver = $this->record->type === 'payment'
                    ? app(PaymentGatewayManager::class)->for($this->record->provider, $this->record)
                    : app(ShippingProviderManager::class)->for($this->record->provider, $this->record);
                $result = $driver->testConnection();
                $this->record->markTested($result->successful, $result->successful ? null : $result->message);
                Notification::make()->title($result->message)->color($result->successful ? 'success' : 'danger')->send();
            }),
            Action::make('disconnect')->label('Desconectar')->color('danger')->requiresConfirmation()->action(function (): void {
                $this->record->update(['enabled' => false, 'credentials' => [], 'connected_at' => null, 'last_test_status' => 'disconnected']);
                Notification::make()->title('Integração desconectada')->success()->send();
            }),
        ];
    }
}
