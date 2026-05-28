<?php

namespace App\Livewire\Checkout;

use App\Support\Cart\CartManager;
use App\Support\Checkout\CreateOrderFromCart;
use Illuminate\Contracts\View\View;
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

    public string $payment_method = 'pix';

    public string $notes = '';

    public bool $privacy_accepted = false;

    public ?string $checkoutError = null;

    public function placeOrder(CartManager $cart, CreateOrderFromCart $createOrder)
    {
        $validated = $this->validate();

        if ($cart->items()->isEmpty()) {
            $this->checkoutError = 'Seu carrinho esta vazio.';

            return null;
        }

        try {
            $order = $createOrder($cart, $validated);
        } catch (Throwable $exception) {
            report($exception);

            $this->checkoutError = 'Nao foi possivel finalizar o pedido. Revise os dados e tente novamente.';

            return null;
        }

        $this->dispatch('cart-updated');

        return $this->redirectRoute('orders.status', $order, navigate: true);
    }

    public function render(CartManager $cart): View
    {
        return view('livewire.checkout.checkout-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->formattedSubtotal(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['required', 'string', 'min:10', 'max:20'],
            'fulfillment_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'postal_code' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:12'],
            'street' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:160'],
            'number' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'city' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'max:120'],
            'state' => [Rule::requiredIf($this->fulfillment_method === 'delivery'), 'nullable', 'string', 'size:2'],
            'payment_method' => ['required', Rule::in(['pix', 'credit_card', 'boleto'])],
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
            'number' => 'numero',
            'neighborhood' => 'bairro',
            'city' => 'cidade',
            'state' => 'estado',
            'payment_method' => 'forma de pagamento',
            'privacy_accepted' => 'politica de privacidade',
        ];
    }
}
