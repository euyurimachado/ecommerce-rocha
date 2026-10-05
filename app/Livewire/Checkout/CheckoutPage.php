<?php

namespace App\Livewire\Checkout;

use App\Enums\PaymentStatus;
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
use App\Support\Payments\PaymentResult;
use App\Support\Shipping\ShippingItem;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\Shipping\ShippingQuoteRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class CheckoutPage extends Component
{
    private const PAYMENT_ATTEMPT_SESSION_KEY = 'checkout.payment_attempt_id';

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

    public ?int $card_installments = 1;

    public array $shipping_quotes = [];

    public ?string $selected_shipping = null;

    public string $payment_attempt_id = '';

    public string $notes = '';

    public bool $privacy_accepted = false;

    public ?string $checkoutError = null;

    public ?string $addressLookupError = null;

    public ?string $existingOrderCode = null;

    public bool $paymentAttemptUncertain = false;

    public ?string $paymentAttemptMessage = null;

    public function mount(CartManager $cart): void
    {
        $attemptId = session()->get(self::PAYMENT_ATTEMPT_SESSION_KEY);
        if (! is_string($attemptId) || ! Str::isUuid($attemptId)) {
            $attemptId = (string) Str::uuid();
            session()->put(self::PAYMENT_ATTEMPT_SESSION_KEY, $attemptId);
        }
        $this->payment_attempt_id = $attemptId;
        $store = StoreSetting::current();
        $this->city = (string) $store->city;
        $this->state = (string) $store->state;

        $gateway = app(PaymentGatewayManager::class)->active();
        if ($gateway) {
            $this->payment_method = $gateway->capabilities()->pix ? 'pix' : 'credit_card';
        }

        $this->restorePaymentAttempt($attemptId, $cart);
    }

    public function placeOrder(CartManager $cart, CreateOrderFromCart $createOrder, PaymentGatewayManager $gateways)
    {
        $attemptId = (string) session()->get(self::PAYMENT_ATTEMPT_SESSION_KEY, '');
        if (! Str::isUuid($attemptId)) {
            $attemptId = (string) Str::uuid();
            session()->put(self::PAYMENT_ATTEMPT_SESSION_KEY, $attemptId);
        }
        $this->payment_attempt_id = $attemptId;

        $order = Order::query()->where('payment_idempotency_key', $attemptId)->first();
        $payment = $order
            ? Payment::query()->where('idempotency_key', $attemptId)->where('order_id', $order->id)->first()
            : null;

        if ($order && $payment && $this->isTerminalPayment($payment) && $cart->items()->isNotEmpty()) {
            $attemptId = (string) Str::uuid();
            session()->put(self::PAYMENT_ATTEMPT_SESSION_KEY, $attemptId);
            $this->payment_attempt_id = $attemptId;
            $order = null;
            $payment = null;
            $this->existingOrderCode = null;
            $this->paymentAttemptUncertain = false;
        }

        if ($order && $payment && filled($payment->provider_payment_id)) {
            return redirect()->route('orders.status', ['order' => $order->code]);
        }

        if ($order && $payment && ! $this->isUnresolvedPayment($payment)) {
            return redirect()->route('orders.status', ['order' => $order->code]);
        }

        $gateway = $order
            ? $gateways->for($order->payment_provider)
            : $gateways->active();
        if (! $gateway) {
            $this->checkoutError = 'Nenhum meio de pagamento online está configurado. Entre em contato com a loja.';

            return null;
        }

        if ($order && $payment) {
            $this->payment_method = $payment->method;
            $this->validateUncertainAttemptFields();
            $validated = [];
        } else {
            $this->normalizeFields();
            $validated = $this->validate();
        }

        if (! $order && $cart->items()->isEmpty()) {
            $this->checkoutError = 'Seu carrinho está vazio.';

            return null;
        }

        try {
            $provider = $order?->payment_provider ?? $gateways->provider();
            $isRetry = (bool) $order;

            if (! $order) {
                $shippingQuote = $this->selectedShippingQuote();
                $validated += [
                    'payment_provider' => $provider,
                    'payment_status' => 'pending',
                    'payment_idempotency_key' => $attemptId,
                    'shipping_quote' => $shippingQuote,
                ];

                [$order, $payment] = DB::transaction(function () use ($cart, $createOrder, $validated, $provider, $attemptId): array {
                    $order = Order::query()->where('payment_idempotency_key', $attemptId)->first()
                        ?? $createOrder($cart, $validated, clearCart: false, recordSale: false);
                    $payment = Payment::query()->firstOrCreate(
                        ['idempotency_key' => $attemptId],
                        [
                            'order_id' => $order->id,
                            'provider' => $provider,
                            'method' => $this->payment_method,
                            'amount_cents' => $order->total_cents,
                            'status' => PaymentStatus::Pending,
                            'metadata' => ['attempt_state' => 'new'],
                        ],
                    );

                    return [$order, $payment];
                });
            }

            $payment ??= Payment::query()->where('idempotency_key', $attemptId)->where('order_id', $order->id)->firstOrFail();
            $this->payment_method = $payment->method;
            $payment->forceFill(['metadata' => array_merge($payment->metadata ?? [], ['attempt_state' => 'submitting'])])->save();
            $order->forceFill(['status' => 'payment_pending'])->save();
            $result = $gateway->createPayment(new PaymentRequest(
                order: $order,
                payment: $payment,
                method: $this->payment_method,
                token: $this->card_token,
                paymentMethodId: $this->card_payment_method_id,
                installments: $this->card_installments ?? 1,
                issuerId: $this->card_issuer_id,
                identificationType: $this->card_identification_type,
                identificationNumber: $this->card_identification_number,
                reconcileFirst: $isRetry,
            ));
            $this->persistPaymentResult($order, $payment, $result);

            if (in_array($result->status, [PaymentStatus::Paid, PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Refunded], true)) {
                $this->paymentAttemptMessage = $result->status === PaymentStatus::Failed
                    ? 'Pagamento recusado. Confira os dados do cartão ou tente outro cartão.'
                    : null;
            }

            if (! $isRetry) {
                $cart->coupon()?->increment('used_count');
            }
            $cart->clear();
            $this->dispatch('cart-updated');

            return $result->redirectUrl
                ? redirect()->away($result->redirectUrl)
                : redirect()->route('orders.status', ['order' => $order->code]);
        } catch (InvalidArgumentException $exception) {
            if (isset($payment) && $payment->exists) {
                $payment->forceFill(['metadata' => array_merge($payment->metadata ?? [], ['attempt_state' => 'uncertain'])])->save();
                $this->paymentAttemptUncertain = true;
                $this->existingOrderCode = $order?->code;
                $this->paymentAttemptMessage = 'Estamos confirmando o pagamento. Não tente pagar novamente com uma nova tentativa.';
            } else {
                $this->checkoutError = $exception->getMessage();
            }

            return null;
        } catch (Throwable $exception) {
            report($exception);

            if (isset($payment) && $payment->exists) {
                $payment->forceFill(['metadata' => array_merge($payment->metadata ?? [], ['attempt_state' => 'uncertain'])])->save();
                $this->paymentAttemptUncertain = true;
                $this->existingOrderCode = $order?->code;
                $this->paymentAttemptMessage = 'Estamos confirmando o pagamento. Não tente pagar novamente com uma nova tentativa.';
            } else {
                $this->checkoutError = 'Não foi possível iniciar o pagamento. Revise os dados e tente novamente.';
            }

            return null;
        }

        return null;
    }

    public function verifyPaymentAttempt(PaymentGatewayManager $gateways)
    {
        $attemptId = (string) session()->get(self::PAYMENT_ATTEMPT_SESSION_KEY, '');
        $order = Order::query()->where('payment_idempotency_key', $attemptId)->first();
        $payment = $order
            ? Payment::query()->where('order_id', $order->id)->where('idempotency_key', $attemptId)->first()
            : null;

        if (! $order || ! $payment) {
            $this->paymentAttemptMessage = 'Não encontramos uma tentativa de pagamento para verificar.';

            return null;
        }

        try {
            $gateway = $gateways->for($order->payment_provider);
            $result = $gateway instanceof MercadoPagoGateway
                ? $gateway->reconcilePayment($order, $payment)
                : null;

            if (! $result) {
                $this->paymentAttemptMessage = 'O pagamento ainda não foi localizado. Aguarde alguns instantes e verifique novamente antes de tentar outro pagamento.';

                return null;
            }

            $this->persistPaymentResult($order, $payment, $result);
            $this->paymentAttemptUncertain = false;
            $this->existingOrderCode = $order->code;

            return redirect()->route('orders.status', ['order' => $order->code]);
        } catch (Throwable $exception) {
            report($exception);
            $this->paymentAttemptMessage = 'Ainda não foi possível confirmar o pagamento. Aguarde e tente verificar novamente.';

            return null;
        }
    }

    private function restorePaymentAttempt(string $attemptId, CartManager $cart): void
    {
        $order = Order::query()->where('payment_idempotency_key', $attemptId)->first();
        if (! $order) {
            return;
        }

        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('idempotency_key', $attemptId)
            ->first();

        if ($payment && $this->isTerminalPayment($payment) && $cart->items()->isNotEmpty()) {
            $attemptId = (string) Str::uuid();
            session()->put(self::PAYMENT_ATTEMPT_SESSION_KEY, $attemptId);
            $this->payment_attempt_id = $attemptId;

            return;
        }

        $this->existingOrderCode = $order->code;
        $this->payment_method = $payment?->method ?? $order->payment_method ?? 'pix';
        $this->fulfillment_method = $order->fulfillment_method;
        $this->customer_name = $order->customer_name;
        $this->customer_email = $order->customer_email;
        $this->customer_phone = $order->customer_phone;
        $this->customer_tax_id = (string) $order->customer_tax_id;
        $this->postal_code = (string) $order->postal_code;
        $this->street = (string) $order->street;
        $this->number = (string) $order->number;
        $this->complement = (string) $order->complement;
        $this->neighborhood = (string) $order->neighborhood;
        $this->city = (string) $order->city;
        $this->state = (string) $order->state;
        $this->paymentAttemptUncertain = ! $payment || $this->isUnresolvedPayment($payment);

        $this->paymentAttemptMessage = $this->paymentAttemptUncertain
            ? 'O resultado do pagamento ainda não foi confirmado. Verifique antes de tentar novamente.'
            : match ($payment->status) {
                PaymentStatus::Paid => 'Este pedido já está pago.',
                PaymentStatus::Failed => 'Pagamento recusado. Confira os dados do cartão ou tente outro cartão.',
                PaymentStatus::Cancelled => 'Este pagamento foi cancelado.',
                PaymentStatus::Refunded => 'Este pagamento foi estornado.',
                default => 'Este pagamento está aguardando confirmação. Não envie outro pagamento.',
            };
    }

    private function isUnresolvedPayment(Payment $payment): bool
    {
        if (filled($payment->provider_payment_id)) {
            return false;
        }

        return ! in_array(data_get($payment->metadata, 'attempt_state'), ['resolved', 'terminal'], true)
            && ! $this->isTerminalPayment($payment);
    }

    private function isTerminalPayment(Payment $payment): bool
    {
        return in_array($payment->status, [
            PaymentStatus::Paid,
            PaymentStatus::Failed,
            PaymentStatus::Cancelled,
            PaymentStatus::Refunded,
        ], true);
    }

    private function validateUncertainAttemptFields(): void
    {
        $rules = [
            'card_token' => [Rule::requiredIf($this->payment_method === 'credit_card'), 'nullable', 'string', 'max:512'],
            'card_payment_method_id' => [Rule::requiredIf($this->payment_method === 'credit_card'), 'nullable', 'string', 'max:40'],
            'card_issuer_id' => ['nullable', 'string', 'max:40'],
            'card_identification_type' => ['nullable', 'in:CPF,CNPJ'],
            'card_identification_number' => ['nullable', 'digits_between:11,14'],
            'card_installments' => [Rule::requiredIf($this->payment_method === 'credit_card'), 'nullable', 'integer', 'min:1', 'max:24'],
        ];

        Validator::make([
            'card_token' => $this->card_token,
            'card_payment_method_id' => $this->card_payment_method_id,
            'card_issuer_id' => $this->card_issuer_id,
            'card_identification_type' => $this->card_identification_type,
            'card_identification_number' => $this->card_identification_number,
            'card_installments' => $this->card_installments,
        ], $rules)->validate();
    }

    private function persistPaymentResult(Order $order, Payment $payment, PaymentResult $result): void
    {
        $payment->forceFill([
            'provider_payment_id' => $result->providerPaymentId,
            'status' => $result->status,
            'external_status' => $result->externalStatus,
            'metadata' => array_merge($result->metadata, ['attempt_state' => 'resolved']),
            'paid_at' => $result->status === PaymentStatus::Paid ? now() : null,
        ])->save();
        $order->forceFill([
            'payment_method' => $payment->method,
            'payment_status' => $result->externalStatus ?? $result->status->value,
            'mercado_pago_preference_id' => $payment->provider === 'mercado_pago' ? data_get($result->metadata, 'preference_id') : null,
            'mercado_pago_payment_id' => $payment->provider === 'mercado_pago' ? $result->providerPaymentId : null,
            'pix_qr_code' => $result->pixCode,
            'pix_qr_code_base64' => $result->pixQrCodeBase64,
            'pix_ticket_url' => $result->pixTicketUrl,
            'pix_expires_at' => $result->expiresAt,
            'mercado_pago_init_point' => $payment->provider === 'mercado_pago' ? $result->redirectUrl : null,
            'mercado_pago_sandbox_init_point' => $payment->provider === 'mercado_pago' ? $result->redirectUrl : null,
            'mercado_pago_status_detail' => $payment->provider === 'mercado_pago' ? data_get($result->metadata, 'status_detail') : null,
        ])->save();

        $orderPaymentStatus = match ($result->status) {
            PaymentStatus::Paid => 'payment_approved',
            PaymentStatus::Failed, PaymentStatus::Cancelled => 'payment_rejected',
            PaymentStatus::Refunded => 'payment_refunded',
            default => null,
        };
        if ($orderPaymentStatus) {
            app(UpdateOrderPaymentStatus::class)($order, $orderPaymentStatus);
        }
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
        $items = $cart->items();
        $existingOrder = $this->existingOrderCode
            ? Order::query()->where('code', $this->existingOrderCode)->first()
            : null;
        $selectedQuote = $this->selectedShippingQuote();
        $shippingCents = $this->fulfillment_method === 'pickup'
            ? 0
            : (int) ($selectedQuote['price_cents'] ?? $shipping->calculate($this->fulfillment_method, $cart->subtotalCents()));
        $gateways = app(PaymentGatewayManager::class);
        $gateway = $gateways->active();

        return view('livewire.checkout.checkout-page', [
            'items' => $items,
            'existingOrder' => $existingOrder,
            'subtotal' => $this->paymentAttemptUncertain && $existingOrder
                ? $cart->formatCurrency($existingOrder->subtotal_cents)
                : $cart->formattedSubtotal(),
            'coupon' => $cart->coupon(),
            'discount' => $cart->formattedDiscount(),
            'shipping' => $this->paymentAttemptUncertain && $existingOrder
                ? $cart->formatCurrency($existingOrder->shipping_cents)
                : $shipping->formatted($shippingCents),
            'shippingCents' => $this->paymentAttemptUncertain && $existingOrder ? $existingOrder->shipping_cents : $shippingCents,
            'shippingEstimate' => $this->fulfillment_method === 'pickup'
                ? config('commerce.shipping.pickup_estimate')
                : config('commerce.shipping.delivery_estimate'),
            'total' => $this->paymentAttemptUncertain && $existingOrder
                ? $existingOrder->formatted_total
                : $cart->formatCurrency($cart->totalCents() + $shippingCents),
            'totalCents' => $this->paymentAttemptUncertain && $existingOrder
                ? $existingOrder->total_cents
                : $cart->totalCents() + $shippingCents,
            'paymentCapabilities' => $existingOrder && $this->paymentAttemptUncertain
                ? $gateways->for($existingOrder->payment_provider)->capabilities()
                : $gateway?->capabilities(),
            'paymentProvider' => $existingOrder && $this->paymentAttemptUncertain
                ? $existingOrder->payment_provider
                : $gateways->provider(),
            'paymentPublicKey' => $existingOrder && $this->paymentAttemptUncertain && $existingOrder->payment_provider === 'mercado_pago'
                ? IntegrationSetting::query()->where('provider', 'mercado_pago')->where('type', 'payment')->first()?->credential('public_key')
                : $gateways->publicKey(),
        ]);
    }

    protected function rules(): array
    {
        $gateways = app(PaymentGatewayManager::class);
        $attemptId = (string) session()->get(self::PAYMENT_ATTEMPT_SESSION_KEY, '');
        $existingOrder = Str::isUuid($attemptId)
            ? Order::query()->where('payment_idempotency_key', $attemptId)->first()
            : null;
        $paymentProvider = $existingOrder?->payment_provider ?? $gateways->provider();
        $paymentGateway = $existingOrder
            ? $gateways->for($existingOrder->payment_provider)
            : $gateways->active();

        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_email' => ['required', 'email:rfc,filter', 'max:160'],
            'customer_phone' => ['required', 'digits_between:10,11'],
            'customer_tax_id' => [Rule::requiredIf(
                ($this->fulfillment_method === 'delivery'
                    && app(ShippingProviderManager::class)->provider() === 'melhor_envio')
                || ($this->payment_method === 'pix'
                    && $paymentProvider === 'mercado_pago')
            ), 'nullable', 'regex:/^(?:\d{11}|\d{14})$/'],
            'fulfillment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'postal_code' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'digits:8'],
            'street' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:160'],
            'number' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'city' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'state' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'size:2'],
            'payment_method' => ['required', Rule::in($paymentGateway?->capabilities()->methods() ?? [])],
            'selected_shipping' => [Rule::requiredIf(
                $this->fulfillment_method === 'delivery'
                && app(ShippingProviderManager::class)->provider() === 'melhor_envio'
            ), 'nullable', 'string'],
            'card_token' => [Rule::requiredIf($this->payment_method === 'credit_card' && $paymentProvider === 'mercado_pago'), 'nullable', 'string'],
            'card_payment_method_id' => [Rule::requiredIf($this->payment_method === 'credit_card' && $paymentProvider === 'mercado_pago'), 'nullable', 'string', 'max:40'],
            'card_issuer_id' => ['nullable', 'string', 'max:40'],
            'card_identification_type' => ['nullable', 'in:CPF,CNPJ'],
            'card_identification_number' => ['nullable', 'digits_between:11,14'],
            'card_installments' => [Rule::requiredIf($this->payment_method === 'credit_card' && $paymentProvider === 'mercado_pago'), 'nullable', 'integer', 'min:1', 'max:24'],
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
