<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Pedido')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'payment_pending' => 'Aguardando pagamento',
                        'payment_approved' => 'Pagamento aprovado',
                        'payment_rejected' => 'Pagamento recusado',
                        'payment_refunded' => 'Pagamento estornado',
                        'preparing' => 'Em separação',
                        'out_for_delivery' => 'Saiu para entrega',
                        'ready_for_pickup' => 'Pronto para retirada',
                        'delivered' => 'Entregue',
                        'cancelled' => 'Cancelado',
                        default => 'Pedido recebido',
                    }),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('customer_email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Telefone')
                    ->searchable(),
                TextColumn::make('fulfillment_method')
                    ->label('Recebimento')
                    ->formatStateUsing(fn (string $state): string => $state === 'pickup' ? 'Retirada' : 'Entrega'),
                TextColumn::make('payment_method')
                    ->label('Pagamento')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mercado_pago' => 'Mercado Pago',
                        'credit_card' => 'Cartão',
                        'boleto' => 'Boleto',
                        'payment_on_delivery_pix' => 'PIX na entrega',
                        'payment_on_delivery_card' => 'Cartão na entrega',
                        default => 'Pix',
                    }),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'R$ '.number_format($state / 100, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'received' => 'Pedido recebido',
                        'payment_pending' => 'Aguardando pagamento',
                        'payment_approved' => 'Pagamento aprovado',
                        'payment_rejected' => 'Pagamento recusado',
                        'payment_refunded' => 'Pagamento estornado',
                        'preparing' => 'Em separação',
                        'out_for_delivery' => 'Saiu para entrega',
                        'ready_for_pickup' => 'Pronto para retirada',
                        'delivered' => 'Entregue',
                        'cancelled' => 'Cancelado',
                    ]),
                SelectFilter::make('fulfillment_method')
                    ->label('Recebimento')
                    ->options([
                        'delivery' => 'Entrega local',
                        'pickup' => 'Retirada na loja',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Pagamento')
                    ->options([
                        'mercado_pago' => 'Mercado Pago',
                        'pix' => 'Pix',
                        'credit_card' => 'Cartão',
                        'boleto' => 'Boleto',
                        'payment_on_delivery_pix' => 'PIX na entrega',
                        'payment_on_delivery_card' => 'Cartão na entrega',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
