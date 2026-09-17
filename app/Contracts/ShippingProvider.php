<?php

namespace App\Contracts;

use App\Models\Order;
use App\Support\Integrations\ConnectionResult;
use App\Support\Shipping\ShipmentResult;
use App\Support\Shipping\ShippingCapabilities;
use App\Support\Shipping\ShippingQuote;
use App\Support\Shipping\ShippingQuoteRequest;

interface ShippingProvider
{
    public function capabilities(): ShippingCapabilities;

    /** @return array<int, ShippingQuote> */
    public function quote(ShippingQuoteRequest $request): array;

    public function testConnection(): ConnectionResult;

    public function createShipment(Order $order): ShipmentResult;

    public function purchaseLabel(Order $order): ShipmentResult;

    public function generateLabel(Order $order): ShipmentResult;

    public function tracking(Order $order): ShipmentResult;
}
