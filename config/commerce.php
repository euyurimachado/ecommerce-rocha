<?php

return [
    'shipping' => [
        'local_delivery_fee_cents' => (int) env('COMMERCE_LOCAL_DELIVERY_FEE_CENTS', 990),
        'free_shipping_threshold_cents' => (int) env('COMMERCE_FREE_SHIPPING_THRESHOLD_CENTS', 25000),
        'delivery_estimate' => env('COMMERCE_DELIVERY_ESTIMATE', 'Entrega local em até 24h úteis'),
        'pickup_estimate' => env('COMMERCE_PICKUP_ESTIMATE', 'Retirada após confirmação no WhatsApp'),
    ],
];
