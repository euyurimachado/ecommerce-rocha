<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do pedido')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Pedido')
                            ->copyable(),
                        TextEntry::make('status_label')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('fulfillment_method_label')
                            ->label('Recebimento'),
                        TextEntry::make('payment_method_label')
                            ->label('Pagamento'),
                        TextEntry::make('mercado_pago_status')
                            ->label('Status Mercado Pago')
                            ->placeholder('-')
                            ->badge(),
                        TextEntry::make('mercado_pago_payment_id')
                            ->label('ID pagamento MP')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('coupon_code')
                            ->label('Cupom')
                            ->placeholder('-'),
                        TextEntry::make('formatted_subtotal')
                            ->label('Subtotal'),
                        TextEntry::make('shipping_cents')
                            ->label('Entrega')
                            ->formatStateUsing(fn (int $state): string => 'R$ '.number_format($state / 100, 2, ',', '.')),
                        TextEntry::make('discount_cents')
                            ->label('Desconto')
                            ->formatStateUsing(fn (int $state): string => 'R$ '.number_format($state / 100, 2, ',', '.')),
                        TextEntry::make('formatted_total')
                            ->label('Total'),
                    ]),
                Section::make('Cliente')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Nome'),
                        TextEntry::make('customer_email')
                            ->label('E-mail')
                            ->copyable(),
                        TextEntry::make('customer_phone')
                            ->label('Telefone')
                            ->copyable(),
                    ]),
                Section::make('Entrega')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('postal_code')
                            ->label('CEP')
                            ->placeholder('-'),
                        TextEntry::make('street')
                            ->label('Rua')
                            ->placeholder('-'),
                        TextEntry::make('number')
                            ->label('Número')
                            ->placeholder('-'),
                        TextEntry::make('neighborhood')
                            ->label('Bairro')
                            ->placeholder('-'),
                        TextEntry::make('city')
                            ->label('Cidade')
                            ->placeholder('-'),
                        TextEntry::make('state')
                            ->label('UF')
                            ->placeholder('-'),
                        TextEntry::make('complement')
                            ->label('Complemento')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Itens')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Itens')
                            ->table([
                                TableColumn::make('Produto'),
                                TableColumn::make('SKU'),
                                TableColumn::make('Qtd.'),
                                TableColumn::make('Unitário'),
                                TableColumn::make('Total'),
                            ])
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Produto'),
                                TextEntry::make('product_sku')
                                    ->label('SKU'),
                                TextEntry::make('quantity')
                                    ->label('Qtd.'),
                                TextEntry::make('unit_price_cents')
                                    ->label('Unitário')
                                    ->formatStateUsing(fn (int $state): string => 'R$ '.number_format($state / 100, 2, ',', '.')),
                                TextEntry::make('line_total_cents')
                                    ->label('Total')
                                    ->formatStateUsing(fn (int $state): string => 'R$ '.number_format($state / 100, 2, ',', '.')),
                            ]),
                    ]),
                Section::make('Observações e auditoria')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('privacy_accepted_at')
                            ->label('Privacidade aceita em')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
