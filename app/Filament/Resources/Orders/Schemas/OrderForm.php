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
                    ->required(),
                Select::make('status')
                    ->options([
                        'received' => 'Pedido recebido',
                        'payment_approved' => 'Pagamento aprovado',
                        'preparing' => 'Em separacao',
                        'out_for_delivery' => 'Saiu para entrega',
                        'ready_for_pickup' => 'Pronto para retirada',
                        'delivered' => 'Entregue',
                        'cancelled' => 'Cancelado',
                    ])
                    ->required()
                    ->default('received'),
                TextInput::make('customer_name')
                    ->required(),
                TextInput::make('customer_email')
                    ->email()
                    ->required(),
                TextInput::make('customer_phone')
                    ->tel()
                    ->required(),
                Select::make('fulfillment_method')
                    ->options([
                        'delivery' => 'Entrega local',
                        'pickup' => 'Retirada na loja',
                    ])
                    ->required(),
                TextInput::make('postal_code'),
                TextInput::make('street'),
                TextInput::make('number'),
                TextInput::make('complement'),
                TextInput::make('neighborhood'),
                TextInput::make('city'),
                TextInput::make('state'),
                Select::make('payment_method')
                    ->options([
                        'pix' => 'Pix',
                        'credit_card' => 'Cartao de credito',
                        'boleto' => 'Boleto',
                    ])
                    ->required(),
                TextInput::make('subtotal_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('shipping_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_cents')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('privacy_accepted_at'),
            ]);
    }
}
