<?php

namespace App\Support\Checkout;

use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\Cart\CartManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CreateOrderFromCart
{
    public function __construct(private readonly ShippingCalculator $shipping) {}

    public function __invoke(
        CartManager $cart,
        array $data,
        bool $clearCart = true,
        bool $recordSale = true,
    ): Order {
        $items = $cart->items();

        if ($items->isEmpty()) {
            throw new RuntimeException('Não é possível finalizar um pedido com carrinho vazio.');
        }

        return DB::transaction(function () use ($cart, $data, $items, $clearCart, $recordSale) {
            $coupon = $cart->coupon();
            $shippingQuote = $data['shipping_quote'] ?? null;
            $shippingCents = $data['fulfillment_method'] === 'pickup'
                ? 0
                : (int) ($shippingQuote['price_cents'] ?? $this->shipping->calculate($data['fulfillment_method'], $cart->subtotalCents()));

            $order = Order::create([
                'code' => $this->generateCode(),
                'status' => 'received',
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'customer_tax_id' => $data['customer_tax_id'] ?? null,
                'fulfillment_method' => $data['fulfillment_method'],
                'postal_code' => $data['postal_code'] ?? null,
                'street' => $data['street'] ?? null,
                'number' => $data['number'] ?? null,
                'complement' => $data['complement'] ?? null,
                'neighborhood' => $data['neighborhood'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_provider' => $data['payment_provider'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'payment_idempotency_key' => $data['payment_idempotency_key'] ?? null,
                'coupon_code' => $coupon?->code,
                'subtotal_cents' => $cart->subtotalCents(),
                'shipping_cents' => $shippingCents,
                'shipping_provider' => $shippingQuote['provider'] ?? ($data['fulfillment_method'] === 'pickup' ? 'pickup' : null),
                'shipping_service_id' => $shippingQuote['service_id'] ?? null,
                'shipping_service_name' => $shippingQuote['service_name'] ?? null,
                'shipping_carrier' => $shippingQuote['carrier'] ?? null,
                'shipping_price_cents' => $shippingCents,
                'shipping_estimated_days' => $shippingQuote['delivery_days'] ?? null,
                'shipping_quote_snapshot' => $shippingQuote,
                'discount_cents' => $cart->discountCents(),
                'total_cents' => $cart->totalCents() + $shippingCents,
                'notes' => $data['notes'] ?? null,
                'privacy_accepted_at' => now(),
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                $variantSelections = $item['variant_selections'] ?? [];
                $inventoryProduct = Product::query()->lockForUpdate()->findOrFail($product->id);

                $inventoryProduct->ensureStockAvailable($variantSelections, $quantity);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $item['product_sku'],
                    'variant_summary' => $item['variant_summary'],
                    'variant_selections' => $variantSelections ?: null,
                    'brand_name' => $product->brand?->name,
                    'category_name' => $product->category?->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $item['unit_price_cents'],
                    'line_total_cents' => $item['line_total_cents'],
                ]);

                if ($recordSale) {
                    $inventoryProduct->decrementStockForSelections($variantSelections, $quantity);
                    $inventoryProduct->increment('sales_count', $quantity);
                }
            }

            if ($clearCart) {
                $cart->clear();
                $coupon?->increment('used_count');
            }

            return $order->refresh();
        });
    }

    private function generateCode(): string
    {
        $prefix = str(StoreSetting::current()->short_name ?: StoreSetting::current()->name ?: 'LO')
            ->ascii()
            ->explode(' ')
            ->filter()
            ->map(fn (string $word): string => mb_substr($word, 0, 1))
            ->implode('');
        $prefix = Str::upper(Str::substr($prefix ?: 'LO', 0, 3));

        do {
            $code = $prefix.now()->format('ymd').Str::upper(Str::random(5));
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
