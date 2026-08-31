<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Order extends Model
{
    use Notifiable;

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
        'payment_provider',
        'payment_status',
        'payment_idempotency_key',
        'mercado_pago_preference_id',
        'mercado_pago_payment_id',
        'mercado_pago_status',
        'mercado_pago_status_detail',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_expires_at',
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
            'pix_expires_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function routeNotificationForMail(): string
    {
        return $this->customer_email;
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
            default => 'Pedido realizado',
        };
    }

    public function getFulfillmentMethodLabelAttribute(): string
    {
        return $this->fulfillment_method === 'pickup' ? 'Retirada na loja' : 'Entrega local';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'card' => 'Cartão',
            'pix' => 'PIX',
            default => 'Mercado Pago',
        };
    }

    public function getPaymentMessageAttribute(): string
    {
        return match ($this->payment_status) {
            'approved' => 'Pagamento aprovado! Já recebemos seu pedido e vamos iniciar a preparação para entrega.',
            'rejected', 'cancelled' => 'Pagamento não aprovado. Confira os dados e tente novamente com segurança.',
            'pending', 'in_process' => $this->payment_method === 'pix'
                ? 'Seu pedido foi criado e está aguardando a confirmação do pagamento via PIX. Assim que o pagamento for confirmado, iniciaremos a preparação.'
                : 'Seu pagamento está sendo processado. Avisaremos assim que houver uma atualização.',
            default => 'Seu pedido foi realizado e o estado do pagamento será atualizado por aqui.',
        };
    }
}
