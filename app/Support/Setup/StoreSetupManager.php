<?php

namespace App\Support\Setup;

use App\Enums\SetupMode;
use App\Models\IntegrationSetting;
use App\Models\StoreSetting;
use App\Support\Integrations\ConnectionResult;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\ShippingProviderManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreSetupManager
{
    public function __construct(
        private readonly PaymentGatewayManager $payments,
        private readonly ShippingProviderManager $shipping,
    ) {}

    public function storeRules(bool $withUploads = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'short_name' => ['required', 'string', 'max:40'],
            'slogan' => ['nullable', 'string', 'max:160'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_dark_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => ['required', Rule::in(StoreSetting::FONTS)],
        ];

        if ($withUploads) {
            $rules += [
                'logo' => ['nullable', 'image', 'max:4096'],
                'logo_dark' => ['nullable', 'image', 'max:4096'],
                'favicon' => ['nullable', 'image', 'max:1024'],
                'pwa_icon' => ['nullable', 'image', 'max:4096'],
            ];
        }

        return $rules;
    }

    public function companyRules(): array
    {
        return [
            'tax_id' => ['required', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['required', 'string', 'max:10'],
            'street' => ['required', 'string', 'max:160'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'country' => ['required', Rule::in(['BR'])],
        ];
    }

    public function paymentRules(bool $requireCredentials = false): array
    {
        $credentialRule = $requireCredentials ? 'required_if' : 'nullable';

        return [
            'provider' => ['required', Rule::in(['mercado_pago', 'asaas'])],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'public_key' => [$credentialRule.($requireCredentials ? ':provider,mercado_pago' : ''), 'string'],
            'access_token' => [$credentialRule.($requireCredentials ? ':provider,mercado_pago' : ''), 'string'],
            'webhook_secret' => [$credentialRule.($requireCredentials ? ':provider,mercado_pago' : ''), 'string', 'min:16'],
            'api_key' => [$credentialRule.($requireCredentials ? ':provider,asaas' : ''), 'string'],
            'webhook_token' => [$credentialRule.($requireCredentials ? ':provider,asaas' : ''), 'string', 'min:16'],
        ];
    }

    public function shippingRules(bool $requireCredentials = false): array
    {
        $credentialRule = $requireCredentials ? 'required_if:provider,melhor_envio' : 'nullable';

        return [
            'provider' => ['required', Rule::in(['flat_rate', 'melhor_envio'])],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'name' => ['nullable', 'required_if:provider,flat_rate', 'string', 'max:80'],
            'price_cents' => ['nullable', 'required_if:provider,flat_rate', 'integer', 'min:0'],
            'min_days' => ['nullable', 'required_if:provider,flat_rate', 'integer', 'min:0'],
            'max_days' => ['nullable', 'required_if:provider,flat_rate', 'integer', 'gte:min_days'],
            'free_shipping_threshold_cents' => ['nullable', 'integer', 'min:0'],
            'client_id' => [$credentialRule, 'string'],
            'client_secret' => [$credentialRule, 'string'],
        ];
    }

    /** @param array<string, UploadedFile|null> $uploads */
    public function mergeBrandingAssets(array $store, array $current, array $uploads): array
    {
        foreach (['logo', 'logo_dark', 'favicon', 'pwa_icon'] as $asset) {
            if (($uploads[$asset] ?? null) instanceof UploadedFile) {
                $store[$asset.'_path'] = $uploads[$asset]->store('branding', 'public');
            } elseif (isset($current[$asset.'_path'])) {
                $store[$asset.'_path'] = $current[$asset.'_path'];
            }

            unset($store[$asset]);
        }

        return $store;
    }

    public function paymentCandidate(array $data, array $newCredentials = []): IntegrationSetting
    {
        return $this->candidate('payment', $data, $newCredentials, []);
    }

    public function shippingCandidate(array $data, array $newCredentials = []): IntegrationSetting
    {
        $settings = array_filter([
            'name' => $data['name'] ?? null,
            'price_cents' => $data['price_cents'] ?? null,
            'min_days' => $data['min_days'] ?? null,
            'max_days' => $data['max_days'] ?? null,
            'free_shipping_threshold_cents' => $data['free_shipping_threshold_cents'] ?? null,
            'auto_purchase_label' => $data['auto_purchase_label'] ?? false,
        ], fn (mixed $value): bool => $value !== null);

        return $this->candidate('shipping', $data, $newCredentials, $settings);
    }

    public function testPayment(IntegrationSetting $candidate): ConnectionResult
    {
        $this->validateRequiredCredentials($candidate);

        return $this->payments->for($candidate->provider, $candidate)->testConnection();
    }

    public function testShipping(IntegrationSetting $candidate): ConnectionResult
    {
        $this->validateRequiredCredentials($candidate);

        return $this->shipping->for($candidate->provider, $candidate)->testConnection();
    }

    public function apply(
        SetupMode $mode,
        array $store,
        array $company,
        IntegrationSetting $payment,
        IntegrationSetting $shipping,
        ?callable $beforePersist = null,
    ): StoreSetting {
        return DB::transaction(function () use ($mode, $store, $company, $payment, $shipping, $beforePersist): StoreSetting {
            if ($beforePersist !== null) {
                $beforePersist();
            }
            $attributes = array_merge($store, $company);

            if ($mode === SetupMode::InitialInstall) {
                $settings = StoreSetting::query()->create($attributes + [
                    'installed_at' => now(),
                    'installation_version' => config('installer.version'),
                ]);
            } else {
                $settings = StoreSetting::current();

                if ($settings->exists) {
                    $settings->fill($attributes);

                    if ($settings->installed_at === null) {
                        $settings->installed_at = now();
                        $settings->installation_version ??= config('installer.version');
                    }

                    $settings->save();
                } else {
                    $settings = StoreSetting::query()->create($attributes + [
                        'installed_at' => now(),
                        'installation_version' => config('installer.version'),
                    ]);
                }
            }

            $this->persistIntegration($payment);
            $this->persistIntegration($shipping);

            return $settings;
        });
    }

    public function persistIntegration(IntegrationSetting $candidate): IntegrationSetting
    {
        $integration = IntegrationSetting::query()->firstOrNew([
            'type' => $candidate->type,
            'provider' => $candidate->provider,
        ]);
        $integration->fill([
            'enabled' => true,
            'environment' => $candidate->environment,
            'credentials' => array_merge($integration->credentials ?? [], $candidate->credentials ?? []),
            'settings' => $candidate->settings ?? [],
            'last_tested_at' => now(),
            'last_test_status' => 'connected',
            'last_error' => null,
            'connected_at' => $integration->connected_at ?? ($candidate->connected_at ?: now()),
        ])->save();

        return $integration;
    }

    private function candidate(string $type, array $data, array $newCredentials, array $settings): IntegrationSetting
    {
        $existing = IntegrationSetting::query()
            ->where('type', $type)
            ->where('provider', $data['provider'])
            ->first();
        $credentials = array_merge(
            $existing?->credentials ?? $this->legacyCredentials($type, $data['provider']),
            array_filter($newCredentials, fn (mixed $value): bool => filled($value)),
        );

        return new IntegrationSetting([
            'type' => $type,
            'provider' => $data['provider'],
            'enabled' => true,
            'environment' => $data['environment'],
            'credentials' => $credentials,
            'settings' => $settings,
            'connected_at' => $existing?->connected_at,
        ]);
    }

    private function legacyCredentials(string $type, string $provider): array
    {
        if ($type !== 'payment' || $provider !== 'mercado_pago') {
            return [];
        }

        return array_filter([
            'public_key' => config('services.mercado_pago.public_key'),
            'access_token' => config('services.mercado_pago.access_token'),
            'webhook_secret' => config('services.mercado_pago.webhook_secret'),
        ], fn (mixed $value): bool => filled($value));
    }

    private function validateRequiredCredentials(IntegrationSetting $candidate): void
    {
        $required = match ([$candidate->type, $candidate->provider]) {
            ['payment', 'mercado_pago'] => ['public_key', 'access_token'],
            ['payment', 'asaas'] => ['api_key'],
            ['shipping', 'melhor_envio'] => ['client_id', 'client_secret', 'access_token'],
            default => [],
        };
        $missing = array_values(array_filter($required, fn (string $key): bool => blank($candidate->credential($key))));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'provider' => 'Complete ou conecte as credenciais obrigatórias antes de ativar este provider: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
