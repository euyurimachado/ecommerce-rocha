<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approvePayment')
                ->label('Pagamento aprovado')
                ->color('success')
                ->visible(fn (Order $record): bool => $record->status === 'received')
                ->action(fn (Order $record) => $record->update(['status' => 'payment_approved'])),
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
            EditAction::make(),
        ];
    }
}
