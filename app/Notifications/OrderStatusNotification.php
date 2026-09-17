<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order, public readonly string $event)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.orders.status', [
                'order' => $this->order,
                'event' => $this->event,
                'headline' => $this->headline(),
                'bodyText' => $this->message(),
            ]);
    }

    private function subject(): string
    {
        $storeName = StoreSetting::current()->name ?: config('app.name');

        return match ($this->event) {
            'preparing' => "Pedido #{$this->order->code} está em separação — {$storeName}",
            'out_for_delivery' => "Pedido #{$this->order->code} foi enviado — {$storeName}",
            'delivered' => "Pedido #{$this->order->code} foi entregue — {$storeName}",
            default => "Pedido #{$this->order->code} realizado — {$storeName}",
        };
    }

    private function headline(): string
    {
        return match ($this->event) {
            'preparing' => 'Pedido em separação',
            'out_for_delivery' => 'Pedido enviado',
            'delivered' => 'Pedido entregue',
            default => 'Pedido realizado',
        };
    }

    private function message(): string
    {
        $tracking = $this->order->tracking_code ? " Código de rastreamento: {$this->order->tracking_code}." : '';

        return match ($this->event) {
            'preparing' => 'Seu pagamento foi confirmado e a equipe já está separando os produtos.',
            'out_for_delivery' => 'Seu pedido foi postado ou está em trânsito.'.$tracking.' Acompanhe as próximas atualizações pelo link abaixo.',
            'delivered' => 'A entrega do seu pedido foi concluída. Obrigado por comprar com '.(StoreSetting::current()->name ?: config('app.name')).'!',
            default => match ($this->order->payment_method) {
                'pix' => 'Seu pedido foi criado e aguarda a confirmação do pagamento via PIX.',
                'card', 'credit_card' => 'Seu pedido foi criado. O estado do pagamento com cartão será atualizado após o processamento.',
                default => 'Seu pedido foi criado. Conclua o pagamento no ambiente seguro do gateway.',
            },
        };
    }
}
