<?php

namespace App\Livewire\Checkout;

use App\Support\Cart\CartManager;
use App\Support\Checkout\CreateOrderFromCart;
use App\Support\Checkout\ShippingCalculator;
use App\Support\Payments\MercadoPago\MercadoPagoClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class CheckoutPage extends Component
{
    public string $customer_name = '';

    public string $customer_email = '';

    public string $customer_phone = '';

    public string $fulfillment_method = 'delivery';

    public string $postal_code = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = 'Campos dos Goytacazes';

    public string $state = 'RJ';

    public string $payment_method = 'mercado_pago';

    public string $notes = '';

    public bool $privacy_accepted = false;

    public ?string $checkoutError = null;

    public ?string $addressLookupError = null;

    public function placeOrder(CartManager $cart, CreateOrderFromCart $createOrder, MercadoPagoClient $mercadoPago)
    {
        $this->normalizeFields();

        $validated = $this->validate();

        if ($cart->items()->isEmpty()) {
            $this->checkoutError = 'Seu carrinho está vazio.';

            return null;
        }

        try {
            $isMercadoPago = $validated['payment_method'] === 'mercado_pago';

            $order = $createOrder(
                $cart,
                $validated,
                clearCart: ! $isMercadoPago,
                recordSale: ! $isMercadoPago,
            );

            if ($order->payment_method === 'mercado_pago') {
                try {
                    $preference = $mercadoPago->createPreference($order);
                } catch (Throwable $exception) {
                    $order->delete();

                    throw $exception;
                }

                $order->forceFill([
                    'status' => 'payment_pending',
                    'mercado_pago_preference_id' => $preference['id'] ?? null,
                    'mercado_pago_init_point' => $preference['init_point'] ?? null,
                    'mercado_pago_sandbox_init_point' => $preference['sandbox_init_point'] ?? null,
                ])->save();

                $cart->coupon()?->increment('used_count');
                $cart->clear();
                $this->dispatch('cart-updated');

                return redirect()->away(
                    $mercadoPago->shouldUseSandboxInitPoint()
                        ? ($preference['sandbox_init_point'] ?? $preference['init_point'])
                        : $preference['init_point']
                );
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->checkoutError = 'Não foi possível finalizar o pedido. Revise os dados e tente novamente.';

            return null;
        }

        $this->dispatch('cart-updated');

        return $this->redirectRoute('orders.status', ['order' => $order->code], navigate: true);
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
    }

    public function render(CartManager $cart, ShippingCalculator $shipping): View
    {
        $shippingCents = $shipping->calculate($this->fulfillment_method, $cart->subtotalCents());

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
        ]);
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_email' => ['required', 'email:rfc,filter', 'max:160'],
            'customer_phone' => ['required', 'digits_between:10,11'],
            'fulfillment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'postal_code' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'digits:8'],
            'street' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:160'],
            'number' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'city' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'state' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'size:2'],
            'payment_method' => ['required', Rule::in([
                'mercado_pago',
                'pix',
                'credit_card',
                'boleto',
                'payment_on_delivery_pix',
                'payment_on_delivery_card',
            ])],
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
}
