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
        'customer_tax_id',
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
        'shipping_provider',
        'shipping_service_id',
        'shipping_service_name',
        'shipping_carrier',
        'shipping_price_cents',
        'shipping_estimated_days',
        'shipping_external_id',
        'shipping_invoice_key',
        'tracking_code',
        'shipping_status',
        'shipping_external_status',
        'shipping_label_url',
        'shipping_quote_snapshot',
        'shipping_posted_at',
        'shipping_delivered_at',
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
            'shipping_quote_snapshot' => 'array',
            'shipping_posted_at' => 'datetime',
            'shipping_delivered_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
        return $this->fulfillment_method === 'pickup'
            ? 'Retirada na loja'
            : ($this->shipping_service_name ?: 'Entrega');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'card', 'credit_card' => 'Cartão de crédito',
            'pix' => 'PIX',
            'mercado_pago' => 'Mercado Pago',
            default => ucfirst(str_replace('_', ' ', (string) $this->payment_method)),
        };
    }

    public function getPaymentMessageAttribute(): string
    {
        return match ($this->payment_status) {
            'approved', 'paid' => 'Pagamento aprovado! Já recebemos seu pedido e vamos iniciar a preparação para entrega.',
            'rejected', 'failed', 'cancelled' => 'Pagamento não aprovado. Confira os dados e tente novamente com segurança.',
            'pending', 'in_process', 'processing' => $this->payment_method === 'pix'
                ? 'Seu pedido foi criado e está aguardando a confirmação do pagamento via PIX. Assim que o pagamento for confirmado, iniciaremos a preparação.'
                : 'Seu pagamento está sendo processado. Avisaremos assim que houver uma atualização.',
            'refunded' => 'O pagamento deste pedido foi estornado.',
            default => 'Seu pedido foi realizado e o estado do pagamento será atualizado por aqui.',
        };
    }
}
