<?php

namespace App\Support\Payments\MercadoPago;

use Illuminate\Http\Request;

class MercadoPagoSignatureValidator
{
    public function isValid(Request $request): bool
    {
        $secret = config('services.mercado_pago.webhook_secret');

        if (! $secret) {
            return true;
        }

        $signature = (string) $request->header('x-signature', '');
        $requestId = (string) $request->header('x-request-id', '');

        if ($signature === '' || $requestId === '') {
            return false;
        }

        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return $key && $value ? [trim($key) => trim($value)] : [];
            });

        $timestamp = $parts->get('ts');
        $hash = $parts->get('v1');
        $dataId = (string) ($request->query('data.id') ?: data_get($request->json()->all(), 'data.id', ''));

        if (! $timestamp || ! $hash || $dataId === '') {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }
}
