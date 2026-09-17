<?php

namespace App\Support\Shipping;

use App\Enums\ShippingStatus;
use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;

class UpdateShipment
{
    public function apply(
        Order $order,
        ShipmentResult $result,
        ?string $eventId = null,
        string $eventType = 'shipment.updated',
        ?string $payloadHash = null,
    ): Order {
        return DB::transaction(function () use ($order, $result, $eventId, $eventType, $payloadHash): Order {
            if ($eventId) {
                $event = WebhookEvent::query()->firstOrCreate(
                    ['provider' => 'melhor_envio', 'external_id' => $eventId, 'event_type' => $eventType],
                    ['payload_hash' => $payloadHash ?? hash('sha256', $eventId)],
                );

                if ($event->processed_at) {
                    return $order->refresh();
                }
            }

            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $newOrderStatus = match ($result->status) {
                ShippingStatus::Posted, ShippingStatus::InTransit => 'out_for_delivery',
                ShippingStatus::Delivered => 'delivered',
                default => $order->status,
            };

            $order->forceFill([
                'shipping_external_id' => $result->externalId,
                'shipping_status' => $result->status->value,
                'shipping_external_status' => $result->externalStatus,
                'tracking_code' => $result->trackingCode ?: $order->tracking_code,
                'shipping_label_url' => $result->labelUrl ?: $order->shipping_label_url,
                'shipping_posted_at' => in_array($result->status, [ShippingStatus::Posted, ShippingStatus::InTransit], true)
                    ? ($order->shipping_posted_at ?? now()) : $order->shipping_posted_at,
                'shipping_delivered_at' => $result->status === ShippingStatus::Delivered
                    ? ($order->shipping_delivered_at ?? now()) : $order->shipping_delivered_at,
                'status' => $newOrderStatus,
            ])->save();

            if (isset($event)) {
                $event->forceFill(['processed_at' => now()])->save();
            }

            return $order->refresh();
        });
    }
}
