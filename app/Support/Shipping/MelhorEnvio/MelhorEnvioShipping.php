<?php

namespace App\Support\Shipping\MelhorEnvio;

use App\Contracts\ShippingProvider;
use App\Enums\ShippingStatus;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Support\Integrations\ConnectionResult;
use App\Support\Shipping\ShipmentResult;
use App\Support\Shipping\ShippingCapabilities;
use App\Support\Shipping\ShippingQuote;
use App\Support\Shipping\ShippingQuoteRequest;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class MelhorEnvioShipping implements ShippingProvider
{
    private readonly MelhorEnvioClient $client;

    public function __construct(private readonly IntegrationSetting $integration)
    {
        $this->client = new MelhorEnvioClient($integration);
    }

    public function capabilities(): ShippingCapabilities
    {
        return new ShippingCapabilities(shipments: true, labels: true, tracking: true, webhooks: true);
    }

    public function quote(ShippingQuoteRequest $request): array
    {
        foreach ($request->items as $item) {
            $item->validateDimensions();
        }

        $products = collect($request->items)
            ->filter->requiresShipping
            ->map(fn ($item): array => [
                'id' => (string) $item->productId,
                'width' => $item->widthCm,
                'height' => $item->heightCm,
                'length' => $item->lengthCm,
                'weight' => $item->weightKg,
                'insurance_value' => round($item->valueCents / 100, 2),
                'quantity' => $item->quantity,
            ])->values()->all();

        if ($products === []) {
            return [];
        }

        $payload = [
            'from' => ['postal_code' => preg_replace('/\D/', '', $request->originPostalCode)],
            'to' => ['postal_code' => preg_replace('/\D/', '', $request->destinationPostalCode)],
            'products' => $products,
            'options' => ['receipt' => false, 'own_hand' => false],
        ];
        $key = 'shipping-quote:'.hash('sha256', json_encode([
            'integration' => [$this->integration->getKey(), $this->integration->updated_at?->getTimestamp()],
            'payload' => $payload,
        ]));

        return Cache::remember($key, now()->addMinutes(5), function () use ($payload): array {
            $response = $this->client->post('/api/v2/me/shipment/calculate', $payload);

            if ($response->failed()) {
                throw new RuntimeException('Não foi possível calcular o frete no Melhor Envio.');
            }

            return collect($response->json())
                ->filter(fn (array $quote): bool => ! isset($quote['error']) && isset($quote['id']))
                ->filter(fn (array $quote): bool => ! $this->requiresSeparateLabels($quote))
                ->map(fn (array $quote): ShippingQuote => new ShippingQuote(
                    provider: 'melhor_envio',
                    serviceId: (string) $quote['id'],
                    carrier: (string) data_get($quote, 'company.name', 'Transportadora'),
                    serviceName: (string) ($quote['name'] ?? 'Entrega'),
                    priceCents: $this->moneyToCents($quote['custom_price'] ?? $quote['price'] ?? 0),
                    deliveryDays: (int) ($quote['custom_delivery_time'] ?? $quote['delivery_time'] ?? 0),
                    originalPriceCents: $this->moneyToCents($quote['price'] ?? 0),
                    quoteReference: hash('sha256', json_encode($quote)),
                    packages: $this->normalizePackages((array) ($quote['packages'] ?? [])),
                ))->values()->all();
        });
    }

    public function testConnection(): ConnectionResult
    {
        try {
            $response = $this->client->get('/api/v2/me');

            return $response->successful()
                ? ConnectionResult::success('Melhor Envio conectado.')
                : ConnectionResult::failure('Não foi possível validar o Melhor Envio.');
        } catch (\Throwable) {
            return ConnectionResult::failure('Não foi possível conectar ao Melhor Envio.');
        }
    }

    public function createShipment(Order $order): ShipmentResult
    {
        $order->loadMissing('items.product');
        $payload = $this->shipmentPayload($order);
        $response = $this->client->post('/api/v2/me/cart', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível inserir o envio no carrinho do Melhor Envio.');
        }

        return new ShipmentResult((string) $response->json('id'), ShippingStatus::LabelCreated, 'pending');
    }

    public function purchaseLabel(Order $order): ShipmentResult
    {
        $this->ensureExternalId($order);
        $response = $this->client->post('/api/v2/me/shipment/checkout', ['orders' => [$order->shipping_external_id]]);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível comprar a etiqueta no Melhor Envio.');
        }

        return new ShipmentResult($order->shipping_external_id, ShippingStatus::Paid, 'paid');
    }

    public function generateLabel(Order $order): ShipmentResult
    {
        $this->ensureExternalId($order);
        $response = $this->client->post('/api/v2/me/shipment/generate', ['orders' => [$order->shipping_external_id]]);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível gerar a etiqueta no Melhor Envio.');
        }

        $print = $this->client->post('/api/v2/me/shipment/print', [
            'mode' => 'public', 'orders' => [$order->shipping_external_id],
        ]);

        return new ShipmentResult(
            $order->shipping_external_id,
            ShippingStatus::Generated,
            'generated',
            labelUrl: $print->successful() ? $print->json('url') : null,
        );
    }

    public function tracking(Order $order): ShipmentResult
    {
        $this->ensureExternalId($order);
        $response = $this->client->post('/api/v2/me/shipment/tracking', ['orders' => [$order->shipping_external_id]]);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o rastreamento no Melhor Envio.');
        }

        $data = (array) ($response->json($order->shipping_external_id) ?? data_get($response->json(), '0', []));
        $external = (string) ($data['status'] ?? 'pending');

        return new ShipmentResult(
            $order->shipping_external_id,
            $this->mapStatus($external),
            $external,
            data_get($data, 'tracking'),
        );
    }

    public function mapStatus(string $status): ShippingStatus
    {
        return match (strtolower($status)) {
            'released', 'posted' => ShippingStatus::Posted,
            'in_transit', 'in transit' => ShippingStatus::InTransit,
            'delivered' => ShippingStatus::Delivered,
            'not_delivered', 'delivery_failed' => ShippingStatus::DeliveryFailed,
            'canceled', 'cancelled' => ShippingStatus::Cancelled,
            'paid' => ShippingStatus::Paid,
            'generated' => ShippingStatus::Generated,
            default => ShippingStatus::Pending,
        };
    }

    private function shipmentPayload(Order $order): array
    {
        $store = StoreSetting::current();
        $senderTaxId = preg_replace('/\D/', '', (string) $store->tax_id);
        $recipientTaxId = preg_replace('/\D/', '', (string) $order->customer_tax_id);

        if (! in_array(strlen($senderTaxId), [11, 14], true) || ! in_array(strlen($recipientTaxId), [11, 14], true)) {
            throw new RuntimeException('Informe CPF/CNPJ válidos para remetente e destinatário antes de criar o envio.');
        }

        $products = $order->items->filter(fn ($item) => $item->product?->requires_shipping)
            ->map(function ($item): array {
                $item->product->shippingDimensionsOrFail();

                return [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unitary_value' => round($item->unit_price_cents / 100, 2),
                ];
            })->values()->all();
        $savedPackages = (array) data_get($order->shipping_quote_snapshot, 'packages', []);
        $volumes = $savedPackages !== [] ? $savedPackages : $order->items->filter(fn ($item) => $item->product?->requires_shipping)
            ->map(fn ($item): array => [
                'height' => (float) $item->product->height_cm,
                'width' => (float) $item->product->width_cm,
                'length' => (float) $item->product->length_cm,
                'weight' => (float) $item->product->weight_kg * $item->quantity,
            ])->values()->all();

        return [
            'service' => (int) $order->shipping_service_id,
            'from' => [
                'name' => $store->name, 'phone' => $store->phone, 'email' => $store->email,
                ...$this->documentFields($senderTaxId, (string) $store->state_registration),
                'postal_code' => preg_replace('/\D/', '', (string) $store->postal_code),
                'address' => $store->street, 'number' => $store->number,
                'complement' => $store->complement, 'district' => $store->neighborhood,
                'city' => $store->city, 'state_abbr' => $store->state, 'country_id' => 'BR',
            ],
            'to' => [
                'name' => $order->customer_name, 'phone' => $order->customer_phone, 'email' => $order->customer_email,
                ...$this->documentFields($recipientTaxId),
                'postal_code' => preg_replace('/\D/', '', (string) $order->postal_code),
                'address' => $order->street, 'number' => $order->number,
                'complement' => $order->complement, 'district' => $order->neighborhood,
                'city' => $order->city, 'state_abbr' => $order->state, 'country_id' => 'BR',
            ],
            'products' => $products,
            'volumes' => $volumes,
            'options' => array_filter([
                'insurance_value' => round($order->subtotal_cents / 100, 2),
                'receipt' => false, 'own_hand' => false,
                'invoice' => filled($order->shipping_invoice_key) ? ['key' => $order->shipping_invoice_key] : null,
                'platform' => config('app.name'),
                'tags' => [['tag' => $order->code, 'url' => null]],
            ], fn (mixed $value): bool => $value !== null),
        ];
    }

    private function documentFields(string $taxId, string $stateRegistration = ''): array
    {
        if (strlen($taxId) === 14) {
            return array_filter([
                'company_document' => $taxId,
                'state_register' => filled($stateRegistration) ? $stateRegistration : null,
            ], fn (mixed $value): bool => $value !== null);
        }

        return ['document' => $taxId];
    }

    private function normalizePackages(array $packages): array
    {
        return collect($packages)->map(fn (array $package): array => [
            'height' => (float) data_get($package, 'dimensions.height'),
            'width' => (float) data_get($package, 'dimensions.width'),
            'length' => (float) data_get($package, 'dimensions.length'),
            'weight' => (float) ($package['weight'] ?? 0),
        ])->filter(fn (array $package): bool => min($package) > 0)->values()->all();
    }

    private function requiresSeparateLabels(array $quote): bool
    {
        if (count((array) ($quote['packages'] ?? [])) < 2) {
            return false;
        }

        $carrier = mb_strtolower((string) data_get($quote, 'company.name'));

        return in_array((int) ($quote['id'] ?? 0), [1, 2, 17, 27], true)
            || str_contains($carrier, 'j&t')
            || str_contains($carrier, 'loggi');
    }

    private function ensureExternalId(Order $order): void
    {
        if (blank($order->shipping_external_id)) {
            throw new RuntimeException('Crie o envio antes de operar a etiqueta.');
        }
    }

    private function moneyToCents(string|int|float $value): int
    {
        return (int) round((float) str_replace(',', '.', (string) $value) * 100);
    }
}
