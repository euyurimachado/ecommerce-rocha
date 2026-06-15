<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'code',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'fulfillment_method',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'payment_method',
        'mercado_pago_preference_id',
        'mercado_pago_payment_id',
        'mercado_pago_status',
        'mercado_pago_status_detail',
        'mercado_pago_init_point',
        'mercado_pago_sandbox_init_point',
        'coupon_code',
        'subtotal_cents',
        'shipping_cents',
        'discount_cents',
        'total_cents',
        'notes',
        'privacy_accepted_at',
        'payment_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'privacy_accepted_at' => 'datetime',
            'payment_approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'R$ '.number_format($this->total_cents / 100, 2, ',', '.');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'R$ '.number_format($this->subtotal_cents / 100, 2, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
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
        };
    }

    public function getFulfillmentMethodLabelAttribute(): string
    {
        return $this->fulfillment_method === 'pickup' ? 'Retirada na loja' : 'Entrega local';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'mercado_pago' => 'Mercado Pago',
            'credit_card' => 'Cartão de crédito',
            'boleto' => 'Boleto',
            'payment_on_delivery_pix' => 'PIX na entrega',
            'payment_on_delivery_card' => 'Cartão na entrega',
            default => 'Pix',
        };
    }
}
