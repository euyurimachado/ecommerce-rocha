<?php

namespace App\Http\Controllers;

use App\Support\Payments\PaymentGatewayManager;
use App\Support\Payments\SyncPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function __invoke(string $provider, Request $request, PaymentGatewayManager $gateways, SyncPayment $sync): Response
    {
        $provider = str_replace('-', '_', $provider);
        $gateway = $gateways->for($provider);

        if (! $gateway->validateWebhook($request)) {
            return response('Invalid signature', 401);
        }

        $result = $gateway->handleWebhook($request);
        $sync->apply($provider, $result, hash('sha256', $request->getContent()));

        return response('OK');
    }
}
