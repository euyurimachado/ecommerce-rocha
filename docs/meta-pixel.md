# Meta Pixel

The storefront uses browser Pixel `2203469027275142` (Meta dataset “Página de ofertas”) and loads Meta's browser script only after the visitor grants marketing cookie consent. `META_PIXEL_ID` can override the public ID in the environment; it is not a secret. No Conversions API token or server-side event sender is used.

Events are emitted from storefront page data and successful cart actions:

- `PageView` on a page view after marketing consent.
- `ViewContent` on product pages, with the product database ID and current displayed price.
- `AddToCart` only after the cart action succeeds, with the product database ID and the cart's actual subtotal delta.
- `InitiateCheckout` only when checkout has at least one cart item, with current cart total, quantity, and product IDs.
- `Purchase` on the order status page only when `payment_approved_at` is set and payment status is `approved` or `paid`. The value is the confirmed order total in BRL. A browser storage key suppresses repeat Purchase sends for that order in the same browser profile.

Because this integration is browser-only, a payment confirmed asynchronously after the buyer leaves the order page cannot reliably generate a `Purchase` event. The event is sent when the buyer later visits the order status page with marketing consent. Browser storage also cannot provide cross-device or server-enforced idempotency. Do not interpret the browser event as a substitute for financial records or server-side order status.

Validation commands:

```sh
npm run test:js
npm run build
php artisan test tests/Feature/CartTest.php tests/Feature/CheckoutTest.php tests/Feature/ProductPageTest.php
```
