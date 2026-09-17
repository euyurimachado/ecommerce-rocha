<?php

namespace App\Support\Shipping\MelhorEnvio;

use App\Models\IntegrationSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MelhorEnvioClient
{
    public function __construct(private readonly IntegrationSetting $integration) {}

    public function get(string $path, array $query = []): Response
    {
        return $this->request()->get($path, $query);
    }

    public function post(string $path, array $payload = []): Response
    {
        return $this->request()->post($path, $payload);
    }

    public function tokenUrl(): string
    {
        return $this->rootUrl().'/oauth/token';
    }

    public function authorizeUrl(string $state): string
    {
        return $this->rootUrl().'/oauth/authorize?'.http_build_query([
            'client_id' => $this->integration->credential('client_id'),
            'redirect_uri' => route('integrations.melhor-envio.callback'),
            'response_type' => 'code',
            'scope' => 'cart-read cart-write orders-read shipping-calculate shipping-checkout shipping-companies shipping-generate shipping-print shipping-tracking users-read',
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): array
    {
        return $this->exchange([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('integrations.melhor-envio.callback'),
        ]);
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->rootUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->withUserAgent((string) ($this->integration->setting('user_agent') ?: config('app.name').' <'.config('mail.from.address').'>'))
            ->timeout(25);
    }

    private function accessToken(): string
    {
        $expiresAt = $this->integration->credential('expires_at');
        $token = (string) $this->integration->credential('access_token');

        if ($token !== '' && $expiresAt && now()->addMinutes(5)->lt($expiresAt)) {
            return $token;
        }

        return Cache::lock('melhor-envio-token-refresh-'.$this->integration->getKey(), 20)
            ->block(5, function (): string {
                $this->integration->refresh();
                $expiresAt = $this->integration->credential('expires_at');
                $token = (string) $this->integration->credential('access_token');

                if ($token !== '' && $expiresAt && now()->addMinutes(5)->lt($expiresAt)) {
                    return $token;
                }

                $refreshToken = (string) $this->integration->credential('refresh_token');

                if ($refreshToken === '') {
                    throw new RuntimeException('Melhor Envio precisa ser reconectado.');
                }

                try {
                    $tokens = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
                    $this->storeTokens($tokens);

                    return (string) $tokens['access_token'];
                } catch (\Throwable $exception) {
                    $this->integration->forceFill([
                        'last_test_status' => 'reconnect_required',
                        'last_error' => 'A autorização expirou. Reconecte o Melhor Envio.',
                    ])->save();

                    throw new RuntimeException('A autorização do Melhor Envio expirou. Reconecte a integração.', previous: $exception);
                }
            });
    }

    private function exchange(array $parameters): array
    {
        $response = Http::asForm()->acceptJson()->timeout(20)->post($this->tokenUrl(), array_merge([
            'client_id' => $this->integration->credential('client_id'),
            'client_secret' => $this->integration->credential('client_secret'),
        ], $parameters));

        if ($response->failed() || blank($response->json('access_token'))) {
            throw new RuntimeException('Não foi possível autorizar o Melhor Envio.');
        }

        return $response->json();
    }

    public function storeTokens(array $tokens): void
    {
        $credentials = array_merge($this->integration->credentials ?? [], [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $this->integration->credential('refresh_token'),
            'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 2592000))->toIso8601String(),
        ]);

        $this->integration->forceFill([
            'credentials' => $credentials,
            'connected_at' => now(),
            'last_tested_at' => now(),
            'last_test_status' => 'connected',
            'last_error' => null,
        ])->save();
    }

    private function rootUrl(): string
    {
        return $this->integration->environment === 'production'
            ? 'https://www.melhorenvio.com.br'
            : 'https://sandbox.melhorenvio.com.br';
    }
}
