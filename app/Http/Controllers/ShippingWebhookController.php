<?php

namespace App\Http\Controllers;

use App\Enums\ShippingStatus;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Support\Shipping\ShipmentResult;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\Shipping\UpdateShipment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShippingWebhookController extends Controller
{
    public function __invoke(Request $request, ShippingProviderManager $providers, UpdateShipment $sync): Response
    {
        $integration = IntegrationSetting::query()
            ->where('type', 'shipping')->where('provider', 'melhor_envio')->firstOrFail();
        $secret = (string) $integration->credential('client_secret');
        $signature = preg_replace('/^sha256=/i', '', (string) $request->header('X-ME-Signature'));
        $digest = hash_hmac('sha256', $request->getContent(), $secret, true);
        $validSignature = $signature !== '' && (
            hash_equals(base64_encode($digest), $signature)
            || hash_equals(bin2hex($digest), strtolower($signature))
        );

        if ($secret === '' || ! $validSignature) {
            return response('Invalid signature', 401);
        }

        $externalId = (string) ($request->input('data.id') ?: $request->input('id'));
        $eventType = (string) ($request->input('event') ?: 'shipment.updated');
        $externalStatus = (string) ($request->input('data.status') ?: $request->input('status') ?: 'pending');
        $eventId = (string) ($request->input('event_id') ?: $externalId.':'.$externalStatus);
        $order = Order::query()->where('shipping_external_id', $externalId)->first();

        if (! $order) {
            return response('OK');
        }

        $provider = $providers->for('melhor_envio', $integration);
        $status = method_exists($provider, 'mapStatus') ? $provider->mapStatus($externalStatus) : ShippingStatus::Pending;
        $sync->apply($order, new ShipmentResult(
            externalId: $externalId,
            status: $status,
            externalStatus: $externalStatus,
            trackingCode: $request->input('data.tracking') ?: $request->input('tracking'),
        ), $eventId, $eventType, hash('sha256', $request->getContent()));

        return response('OK');
    }
}
