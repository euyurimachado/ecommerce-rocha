<?php

namespace App\Livewire\Checkout;

use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StoreSetting;
use App\Support\Cart\CartManager;
use App\Support\Checkout\CreateOrderFromCart;
use App\Support\Checkout\ShippingCalculator;
use App\Support\Orders\UpdateOrderPaymentStatus;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Payments\PaymentRequest;
use App\Support\Shipping\ShippingItem;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\Shipping\ShippingQuoteRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class CheckoutPage extends Component
{
    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public string $customer_tax_id = '';

    public string $fulfillment_method = 'delivery';

    public string $postal_code = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public string $payment_method = 'pix';

    public ?string $card_token = null;

    public ?string $card_payment_method_id = null;

    public ?string $card_issuer_id = null;

    public ?string $card_identification_type = null;

    public ?string $card_identification_number = null;

    public int $card_installments = 1;

    public array $shipping_quotes = [];

    public ?string $selected_shipping = null;

    public string $payment_attempt_id = '';

    public string $notes = '';

    public bool $privacy_accepted = false;

    public ?string $checkoutError = null;

    public ?string $addressLookupError = null;

    public function mount(): void
    {
        $this->payment_attempt_id = (string) Str::uuid();
        $store = StoreSetting::current();
        $this->city = (string) $store->city;
        $this->state = (string) $store->state;

        if (IntegrationSetting::active('payment')) {
            $this->payment_method = app(PaymentGatewayManager::class)->active()->capabilities()->pix ? 'pix' : 'credit_card';
        }
    }

    public function placeOrder(CartManager $cart, CreateOrderFromCart $createOrder, PaymentGatewayManager $gateways)
    {
        $this->normalizeFields();

        $validated = $this->validate();

        if ($cart->items()->isEmpty()) {
            $this->checkoutError = 'Seu carrinho está vazio.';

            return null;
        }

        try {
            $gateway = $gateways->active();
            $provider = $gateways->provider();
            $shippingQuote = $this->selectedShippingQuote();
            $validated += [
                'payment_provider' => $provider,
                'payment_status' => 'pending',
                'payment_idempotency_key' => $this->payment_attempt_id,
                'shipping_quote' => $shippingQuote,
            ];

            $order = Order::query()
                ->where('payment_idempotency_key', $this->payment_attempt_id)
                ->first() ?? $createOrder($cart, $validated, clearCart: false, recordSale: false);

            $order->forceFill(['status' => 'payment_pending'])->save();

            $payment = Payment::query()->firstOrCreate(
                ['idempotency_key' => $this->payment_attempt_id],
                [
                    'order_id' => $order->id,
                    'provider' => $provider,
                    'method' => $this->payment_method,
                    'amount_cents' => $order->total_cents,
                    'status' => 'pending',
                ],
            );
            $result = $gateway->createPayment(new PaymentRequest(
                order: $order,
                payment: $payment,
                method: $this->payment_method,
                token: $this->card_token,
                paymentMethodId: $this->card_payment_method_id,
                installments: $this->card_installments,
                issuerId: $this->card_issuer_id,
                identificationType: $this->card_identification_type,
                identificationNumber: $this->card_identification_number,
            ));
            $payment->forceFill([
                'provider_payment_id' => $result->providerPaymentId,
                'status' => $result->status,
                'external_status' => $result->externalStatus,
                'metadata' => $result->metadata,
                'paid_at' => $result->status->value === 'paid' ? now() : null,
            ])->save();
            $order->forceFill([
                'payment_method' => $this->payment_method,
                'payment_status' => $result->externalStatus ?? $result->status->value,
                'mercado_pago_preference_id' => $provider === 'mercado_pago' ? data_get($result->metadata, 'preference_id') : null,
                'mercado_pago_payment_id' => $provider === 'mercado_pago' ? $result->providerPaymentId : null,
                'pix_qr_code' => $result->pixCode,
                'pix_qr_code_base64' => $result->pixQrCodeBase64,
                'pix_ticket_url' => $result->pixTicketUrl,
                'pix_expires_at' => $result->expiresAt,
                'mercado_pago_init_point' => $provider === 'mercado_pago' ? $result->redirectUrl : null,
                'mercado_pago_sandbox_init_point' => $provider === 'mercado_pago' ? $result->redirectUrl : null,
            ])->save();

            $orderPaymentStatus = match ($result->status->value) {
                'paid' => 'payment_approved',
                'failed', 'cancelled' => 'payment_rejected',
                'refunded' => 'payment_refunded',
                default => null,
            };
            if ($orderPaymentStatus) {
                app(UpdateOrderPaymentStatus::class)($order, $orderPaymentStatus);
            }

            $cart->coupon()?->increment('used_count');
            $cart->clear();
            $this->dispatch('cart-updated');

            return $result->redirectUrl
                ? redirect()->away($result->redirectUrl)
                : redirect()->route('orders.status', ['order' => $order->code]);
        } catch (InvalidArgumentException $exception) {
            $this->checkoutError = $exception->getMessage();

            return null;
        } catch (Throwable $exception) {
            report($exception);

            $this->checkoutError = 'Não foi possível finalizar o pedido. Revise os dados e tente novamente.';

            return null;
        }

        return null;
    }

    public function lookupPostalCode(): void
    {
        $digits = $this->onlyDigits($this->postal_code);

        $this->addressLookupError = null;

        if ($digits === '') {
            return;
        }

        if (strlen($digits) !== 8) {
            $this->addressLookupError = 'Informe um CEP com 8 dígitos.';

            return;
        }

        $this->postal_code = $this->formatPostalCode($digits);

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get("https://viacep.com.br/ws/{$digits}/json/");
        } catch (Throwable $exception) {
            report($exception);

            $this->addressLookupError = 'Não foi possível buscar o CEP agora.';

            return;
        }

        if ($response->failed() || $response->json('erro')) {
            $this->addressLookupError = 'CEP não encontrado.';

            return;
        }

        $this->street = (string) ($response->json('logradouro') ?: $this->street);
        $this->neighborhood = (string) ($response->json('bairro') ?: $this->neighborhood);
        $this->city = (string) ($response->json('localidade') ?: $this->city);
        $this->state = strtoupper((string) ($response->json('uf') ?: $this->state));
        $this->loadShippingQuotes();
    }

    public function loadShippingQuotes(): void
    {
        if ($this->fulfillment_method !== 'delivery' || strlen($this->onlyDigits($this->postal_code)) !== 8) {
            $this->shipping_quotes = [];
            $this->selected_shipping = null;

            return;
        }

        try {
            $cart = app(CartManager::class);
            $store = StoreSetting::current();
            $request = new ShippingQuoteRequest(
                originPostalCode: (string) ($store->postal_code ?: config('commerce.shipping.origin_postal_code', '28000000')),
                destinationPostalCode: $this->postal_code,
                items: $cart->items()->map(fn (array $item): ShippingItem => new ShippingItem(
                    productId: $item['product']->id,
                    name: $item['product']->name,
                    quantity: $item['quantity'],
                    valueCents: $item['unit_price_cents'],
                    requiresShipping: (bool) $item['product']->requires_shipping,
                    weightKg: $item['product']->weight_kg ? (float) $item['product']->weight_kg : null,
                    widthCm: $item['product']->width_cm ? (float) $item['product']->width_cm : null,
                    heightCm: $item['product']->height_cm ? (float) $item['product']->height_cm : null,
                    lengthCm: $item['product']->length_cm ? (float) $item['product']->length_cm : null,
                ))->all(),
                subtotalCents: $cart->subtotalCents(),
            );
            $this->shipping_quotes = collect(app(ShippingProviderManager::class)->active()->quote($request))
                ->map->toArray()->values()->all();
            $this->selected_shipping = $this->selected_shipping ?: data_get($this->shipping_quotes, '0.service_id');
        } catch (InvalidArgumentException $exception) {
            $this->checkoutError = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);
            $this->checkoutError = 'Não foi possível calcular o frete agora. Tente novamente.';
        }
    }

    public function render(CartManager $cart, ShippingCalculator $shipping): View
    {
        $selectedQuote = $this->selectedShippingQuote();
        $shippingCents = $this->fulfillment_method === 'pickup'
            ? 0
            : (int) ($selectedQuote['price_cents'] ?? $shipping->calculate($this->fulfillment_method, $cart->subtotalCents()));
        $gateway = app(PaymentGatewayManager::class)->active();

        return view('livewire.checkout.checkout-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->formattedSubtotal(),
            'coupon' => $cart->coupon(),
            'discount' => $cart->formattedDiscount(),
            'shipping' => $shipping->formatted($shippingCents),
            'shippingCents' => $shippingCents,
            'shippingEstimate' => $this->fulfillment_method === 'pickup'
                ? config('commerce.shipping.pickup_estimate')
                : config('commerce.shipping.delivery_estimate'),
            'total' => $cart->formatCurrency($cart->totalCents() + $shippingCents),
            'totalCents' => $cart->totalCents() + $shippingCents,
            'paymentCapabilities' => $gateway->capabilities(),
            'paymentProvider' => app(PaymentGatewayManager::class)->provider(),
            'paymentPublicKey' => IntegrationSetting::active('payment')?->credential('public_key')
                ?: config('services.mercado_pago.public_key'),
        ]);
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_email' => ['required', 'email:rfc,filter', 'max:160'],
            'customer_phone' => ['required', 'digits_between:10,11'],
            'customer_tax_id' => [Rule::requiredIf(
                ($this->fulfillment_method === 'delivery'
                    && app(ShippingProviderManager::class)->provider() === 'melhor_envio')
                || ($this->payment_method === 'pix'
                    && app(PaymentGatewayManager::class)->provider() === 'mercado_pago')
            ), 'nullable', 'regex:/^(?:\d{11}|\d{14})$/'],
            'fulfillment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'postal_code' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'digits:8'],
            'street' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:160'],
            'number' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'city' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'state' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'size:2'],
            'payment_method' => ['required', Rule::in(app(PaymentGatewayManager::class)->active()->capabilities()->methods())],
            'selected_shipping' => [Rule::requiredIf(
                $this->fulfillment_method === 'delivery'
                && app(ShippingProviderManager::class)->provider() === 'melhor_envio'
            ), 'nullable', 'string'],
            'card_token' => [Rule::requiredIf($this->payment_method === 'credit_card' && app(PaymentGatewayManager::class)->provider() === 'mercado_pago'), 'nullable', 'string'],
            'card_payment_method_id' => [Rule::requiredIf($this->payment_method === 'credit_card' && app(PaymentGatewayManager::class)->provider() === 'mercado_pago'), 'nullable', 'string', 'max:40'],
            'card_issuer_id' => ['nullable', 'string', 'max:40'],
            'card_identification_type' => ['nullable', 'in:CPF,CNPJ'],
            'card_identification_number' => ['nullable', 'digits_between:11,14'],
            'card_installments' => ['integer', 'min:1', 'max:24'],
            'notes' => ['nullable', 'string', 'max:500'],
            'privacy_accepted' => ['accepted'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'customer_name' => 'nome',
            'customer_email' => 'e-mail',
            'customer_phone' => 'telefone',
            'customer_tax_id' => 'CPF ou CNPJ',
            'fulfillment_method' => 'forma de recebimento',
            'postal_code' => 'CEP',
            'street' => 'rua',
            'number' => 'número',
            'neighborhood' => 'bairro',
            'city' => 'cidade',
            'state' => 'estado',
            'payment_method' => 'forma de pagamento',
            'privacy_accepted' => 'política de privacidade',
        ];
    }

    private function normalizeFields(): void
    {
        $this->customer_email = mb_strtolower(trim($this->customer_email));
        $this->customer_phone = $this->onlyDigits($this->customer_phone);
        $this->customer_tax_id = $this->onlyDigits($this->customer_tax_id);
        $this->postal_code = $this->onlyDigits($this->postal_code);
        $this->state = strtoupper(trim($this->state));
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function formatPostalCode(string $digits): string
    {
        return strlen($digits) === 8
            ? substr($digits, 0, 5).'-'.substr($digits, 5)
            : $digits;
    }

    private function selectedShippingQuote(): ?array
    {
        if ($this->fulfillment_method === 'pickup') {
            return null;
        }

        $selected = collect($this->shipping_quotes)
            ->first(fn (array $quote): bool => (string) $quote['service_id'] === (string) $this->selected_shipping);

        if ($selected || app(ShippingProviderManager::class)->provider() !== 'flat_rate') {
            return $selected;
        }

        $cart = app(CartManager::class);
        $quote = app(ShippingProviderManager::class)->active()->quote(new ShippingQuoteRequest(
            originPostalCode: '', destinationPostalCode: $this->postal_code, items: [], subtotalCents: $cart->subtotalCents(),
        ))[0] ?? null;

        return $quote?->toArray();
    }
}
