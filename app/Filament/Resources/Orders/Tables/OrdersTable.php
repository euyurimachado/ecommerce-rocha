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
                        'payment_approved' => 'Pagamento aprovado',
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
                        'credit_card' => 'Cartão',
                        'boleto' => 'Boleto',
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
                        'payment_approved' => 'Pagamento aprovado',
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
                        'pix' => 'Pix',
                        'credit_card' => 'Cartão',
                        'boleto' => 'Boleto',
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
