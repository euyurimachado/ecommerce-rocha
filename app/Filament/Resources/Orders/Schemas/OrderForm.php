<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->required(),
                Select::make('status')
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
                    ])
                    ->required()
                    ->default('received'),
                TextInput::make('customer_name')
                    ->label('Cliente')
                    ->required(),
                TextInput::make('customer_email')
                    ->label('E-mail')
                    ->email()
                    ->required(),
                TextInput::make('customer_phone')
                    ->label('Telefone')
                    ->tel()
                    ->required(),
                Select::make('fulfillment_method')
                    ->options([
                        'delivery' => 'Entrega local',
                        'pickup' => 'Retirada na loja',
                    ])
                    ->required(),
                TextInput::make('postal_code')
                    ->label('CEP'),
                TextInput::make('street')
                    ->label('Rua'),
                TextInput::make('number')
                    ->label('Número'),
                TextInput::make('complement')
                    ->label('Complemento'),
                TextInput::make('neighborhood')
                    ->label('Bairro'),
                TextInput::make('city')
                    ->label('Cidade'),
                TextInput::make('state')
                    ->label('UF'),
                Select::make('payment_method')
                    ->label('Pagamento')
                    ->options([
                        'mercado_pago' => 'Mercado Pago',
                        'pix' => 'Pix',
                        'credit_card' => 'Cartão de crédito',
                        'boleto' => 'Boleto',
                        'payment_on_delivery_pix' => 'PIX na entrega',
                        'payment_on_delivery_card' => 'Cartão na entrega',
                    ])
                    ->required(),
                TextInput::make('subtotal_cents')
                    ->label('Subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('shipping_cents')
                    ->label('Entrega')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_cents')
                    ->label('Desconto')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_cents')
                    ->label('Total')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
                DateTimePicker::make('privacy_accepted_at')
                    ->label('Privacidade aceita em'),
                DateTimePicker::make('payment_approved_at')
                    ->label('Pagamento aprovado em'),
            ]);
    }
}
