<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Payments\MercadoPago\MercadoPagoSignatureValidator;
use App\Support\Payments\MercadoPago\SyncMercadoPagoPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MercadoPagoController extends Controller
{
    public function return(Request $request, Order $order, SyncMercadoPagoPayment $sync): RedirectResponse
    {
        $paymentId = $request->query('payment_id')
            ?: $request->query('collection_id');

        if ($paymentId) {
            try {
                $sync->byPaymentId((string) $paymentId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('orders.status', ['order' => $order->code]);
    }

    public function webhook(
        Request $request,
        MercadoPagoSignatureValidator $validator,
        SyncMercadoPagoPayment $sync,
    ): Response {
        if (! $validator->isValid($request)) {
            return response('Invalid signature', 401);
        }

        $type = $request->query('type')
            ?: $request->input('type')
            ?: $request->input('topic');

        $paymentId = $request->query('data.id')
            ?: data_get($request->json()->all(), 'data.id')
            ?: $request->input('id');

        if ($type === 'payment' && $paymentId) {
            try {
                $sync->byPaymentId((string) $paymentId);
            } catch (\Throwable $exception) {
                report($exception);

                return response('Payment sync failed', 500);
            }
        }

        return response('OK');
    }
}
