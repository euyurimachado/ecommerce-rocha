<?php

namespace App\Filament\Resources\IntegrationSettings\Pages;

use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookEvent;
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

    private function hasHistoricalDependencies(): bool
    {
        $provider = $this->record->provider;

        if ($this->record->type === 'payment') {
            $orders = Order::query()->where('payment_provider', $provider);

            if ($provider === 'mercado_pago') {
                $orders->orWhereNotNull('mercado_pago_payment_id');
            }

            return Payment::query()->where('provider', $provider)->exists()
                || $orders->exists()
                || WebhookEvent::query()->where('provider', $provider)->exists();
        }

        return $this->record->type === 'shipping'
            && Order::query()->where('shipping_provider', $provider)->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connectMelhorEnvio')
                ->label('Conectar Melhor Envio')
                ->icon('heroicon-o-link')
                ->visible(fn (): bool => $this->record->provider === 'melhor_envio')
                ->url(route('integrations.melhor-envio.redirect')),
            Action::make('test')->label('Testar conexão')
                ->tooltip('Valida o Access Token e a conectividade do provider. Não valida webhook_secret nem ativa a integração.')
                ->action(function (): void {
                    $driver = $this->record->type === 'payment'
                        ? app(PaymentGatewayManager::class)->for($this->record->provider, $this->record)
                        : app(ShippingProviderManager::class)->for($this->record->provider, $this->record);
                    $result = $driver->testConnection();
                    $this->record->markTested($result->successful, $result->successful ? null : $result->message);
                    Notification::make()->title($result->successful ? 'Conexão testada com sucesso' : 'Teste de conexão falhou')
                        ->body($result->message)->color($result->successful ? 'success' : 'danger')->send();
                }),
            Action::make('disconnect')->label('Desconectar')->color('danger')
                ->modalHeading(fn (): string => 'Desconectar '.str($this->record->provider)->replace('_', ' ')->title().'?')
                ->modalDescription('Esta ação desativará a integração e removerá as credenciais armazenadas.')
                ->modalSubmitActionLabel('Desconectar')
                ->requiresConfirmation()->action(function (): void {
                    $this->record->update(['enabled' => false, 'credentials' => [], 'connected_at' => null, 'last_test_status' => 'disconnected']);
                    Notification::make()->title('Integração desconectada')->success()->send();
                }),
            Action::make('deleteIntegration')
                ->label('Excluir integração')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->disabled(fn (): bool => $this->hasHistoricalDependencies())
                ->tooltip(fn (): ?string => $this->hasHistoricalDependencies()
                        ? 'Há registros históricos deste provider que ainda dependem da configuração para validar notificações ou sincronização. Desconecte a integração para impedir novas operações sem perder esse histórico.'
                        : null)
                ->modalHeading('Excluir integração?')
                ->modalDescription(fn (): string => $this->record->enabled
                        ? 'Esta integração está ativa. Ao excluí-la, o meio de pagamento deixará de estar disponível no checkout. A configuração e as credenciais armazenadas serão removidas.'
                        : 'Esta ação removerá a configuração e as credenciais armazenadas deste provider.')
                ->modalSubmitActionLabel('Excluir integração')
                ->requiresConfirmation()
                ->action(function (): void {
                    if ($this->hasHistoricalDependencies()) {
                        Notification::make()->title('A integração precisa ser preservada')
                            ->body('Há registros históricos deste provider que podem depender da configuração para validar notificações ou sincronizar atualizações futuras. Use Desconectar para impedir novas operações sem perder esse histórico.')
                            ->warning()->send();

                        return;
                    }

                    $this->record->delete();
                    Notification::make()->title('Integração excluída')->success()->send();
                    $this->redirect(IntegrationSettingResource::getUrl('index'));
                }),
        ];
    }
}
