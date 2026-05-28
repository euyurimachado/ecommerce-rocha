<?php

namespace App\Support\Checkout;

use App\Models\Order;
use App\Support\Cart\CartManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CreateOrderFromCart
{
    public function __invoke(CartManager $cart, array $data): Order
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            throw new RuntimeException('Nao e possivel finalizar um pedido com carrinho vazio.');
        }

        return DB::transaction(function () use ($cart, $data, $items) {
            $order = Order::create([
                'code' => $this->generateCode(),
                'status' => 'received',
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'fulfillment_method' => $data['fulfillment_method'],
                'postal_code' => $data['postal_code'] ?? null,
                'street' => $data['street'] ?? null,
                'number' => $data['number'] ?? null,
                'complement' => $data['complement'] ?? null,
                'neighborhood' => $data['neighborhood'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'payment_method' => $data['payment_method'],
                'subtotal_cents' => $cart->subtotalCents(),
                'shipping_cents' => 0,
                'discount_cents' => 0,
                'total_cents' => $cart->subtotalCents(),
                'notes' => $data['notes'] ?? null,
                'privacy_accepted_at' => now(),
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];

                if ($product->available_quantity < $quantity) {
                    throw new RuntimeException("Estoque insuficiente para {$product->name}.");
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'brand_name' => $product->brand?->name,
                    'category_name' => $product->category?->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $product->price_cents,
                    'line_total_cents' => $item['line_total_cents'],
                ]);

                $product->decrement('stock_quantity', $quantity);
                $product->increment('sales_count', $quantity);
            }

            $cart->clear();

            return $order->refresh();
        });
    }

    private function generateCode(): string
    {
        do {
            $code = 'RS'.now()->format('ymd').Str::upper(Str::random(5));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
