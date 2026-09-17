<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Orders\UpdateOrderPaymentStatus;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\Shipping\UpdateShipment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printDelivery')
                ->label('Imprimir entrega')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Order $record): string => route('admin.orders.print', $record))
                ->openUrlInNewTab(),
            Action::make('markPaymentPending')
                ->label('Aguardar pagamento')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->visible(fn (Order $record): bool => ! in_array($record->status, ['payment_pending', 'delivered', 'cancelled'], true))
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->updatePaymentStatus($record, 'payment_pending')),
            Action::make('approvePayment')
                ->label('Pagamento aprovado')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Order $record): bool => ! in_array($record->status, ['payment_approved', 'delivered', 'cancelled'], true))
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->updatePaymentStatus($record, 'payment_approved')),
            Action::make('rejectPayment')
                ->label('Pagamento recusado')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Order $record): bool => ! in_array($record->status, ['payment_rejected', 'payment_refunded', 'delivered', 'cancelled'], true))
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->updatePaymentStatus($record, 'payment_rejected')),
            Action::make('refundPayment')
                ->label('Pagamento estornado')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn (Order $record): bool => $record->status === 'payment_approved')
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->updatePaymentStatus($record, 'payment_refunded')),
            Action::make('startPreparing')
                ->label('Em separação')
                ->color('warning')
                ->visible(fn (Order $record): bool => in_array($record->status, ['received', 'payment_approved'], true))
                ->action(fn (Order $record) => $record->update(['status' => 'preparing'])),
            Action::make('dispatchOrder')
                ->label(fn (Order $record): string => $record->fulfillment_method === 'pickup' ? 'Pronto para retirada' : 'Saiu para entrega')
                ->color('info')
                ->visible(fn (Order $record): bool => $record->status === 'preparing')
                ->action(fn (Order $record) => $record->update([
                    'status' => $record->fulfillment_method === 'pickup' ? 'ready_for_pickup' : 'out_for_delivery',
                ])),
            Action::make('markDelivered')
                ->label('Entregue')
                ->color('success')
                ->visible(fn (Order $record): bool => in_array($record->status, ['out_for_delivery', 'ready_for_pickup'], true))
                ->action(fn (Order $record) => $record->update(['status' => 'delivered'])),
            Action::make('createShipment')
                ->label('Criar envio no Melhor Envio')
                ->icon('heroicon-o-truck')
                ->visible(fn (Order $record): bool => $record->shipping_provider === 'melhor_envio'
                    && $record->payment_approved_at !== null && blank($record->shipping_external_id))
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->runShipmentAction($record, 'createShipment')),
            Action::make('purchaseLabel')
                ->label('Comprar etiqueta')
                ->icon('heroicon-o-credit-card')
                ->visible(fn (Order $record): bool => $record->shipping_provider === 'melhor_envio'
                    && filled($record->shipping_external_id) && $record->shipping_status === 'label_created')
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->runShipmentAction($record, 'purchaseLabel')),
            Action::make('generateLabel')
                ->label('Gerar etiqueta')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn (Order $record): bool => $record->shipping_provider === 'melhor_envio'
                    && in_array($record->shipping_status, ['paid', 'generated'], true))
                ->action(fn (Order $record) => $this->runShipmentAction($record, 'generateLabel')),
            Action::make('printLabel')
                ->label('Visualizar etiqueta')
                ->icon('heroicon-o-printer')
                ->visible(fn (Order $record): bool => filled($record->shipping_label_url))
                ->url(fn (Order $record): string => $record->shipping_label_url)
                ->openUrlInNewTab(),
            Action::make('refreshTracking')
                ->label('Atualizar rastreamento')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Order $record): bool => $record->shipping_provider === 'melhor_envio'
                    && filled($record->shipping_external_id))
                ->action(fn (Order $record) => $this->runShipmentAction($record, 'tracking')),
            EditAction::make(),
        ];
    }

    private function updatePaymentStatus(Order $record, string $status): void
    {
        app(UpdateOrderPaymentStatus::class)($record, $status);

        Notification::make()
            ->title('Status de pagamento atualizado')
            ->success()
            ->send();
    }

    private function runShipmentAction(Order $record, string $method): void
    {
        try {
            $provider = app(ShippingProviderManager::class)->for('melhor_envio');
            $result = $provider->{$method}($record);
            app(UpdateShipment::class)->apply($record, $result);
            Notification::make()->title('Envio atualizado com sucesso')->success()->send();
        } catch (\Throwable $exception) {
            report($exception);
            Notification::make()->title('Não foi possível atualizar o envio')->body($exception->getMessage())->danger()->send();
        }
    }
}
