<?php

namespace App\Filament\Pages;

use App\Enums\SetupMode;
use App\Models\IntegrationSetting;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\Setup\StoreSetupManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class StoreSetupWizard extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Reconfigurar loja';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Assistente de reconfiguração';

    protected static ?string $slug = 'settings/setup';

    protected string $view = 'filament.pages.store-setup-wizard';

    public int $step = 1;

    public array $store = [];

    public array $company = [];

    public array $payment = [];

    public array $shipping = [];

    public array $configuredCredentials = ['payment' => false, 'shipping' => false];

    public mixed $logo = null;

    public mixed $logoDark = null;

    public mixed $favicon = null;

    public mixed $pwaIcon = null;

    public function mount(): void
    {
        abort_unless(StoreSetting::installationDetected(), 404);
        $settings = StoreSetting::current();

        $this->store = Arr::only($settings->toArray(), [
            'name', 'legal_name', 'short_name', 'slogan', 'primary_color', 'primary_dark_color',
            'secondary_color', 'accent_color', 'background_color', 'font_family',
        ]);
        $this->company = Arr::only($settings->toArray(), [
            'tax_id', 'state_registration', 'email', 'phone', 'whatsapp', 'postal_code', 'street',
            'number', 'complement', 'neighborhood', 'city', 'state', 'country',
        ]);

        $payment = IntegrationSetting::active('payment');
        $this->payment = [
            'provider' => $payment?->provider ?? 'mercado_pago',
            'environment' => $payment?->environment ?? (config('services.mercado_pago.sandbox') ? 'sandbox' : 'production'),
            'public_key' => '',
            'access_token' => '',
            'webhook_secret' => '',
            'api_key' => '',
            'webhook_token' => '',
        ];
        $this->configuredCredentials['payment'] = ! empty($payment?->credentials)
            || (filled(config('services.mercado_pago.public_key')) && filled(config('services.mercado_pago.access_token')));

        $shipping = IntegrationSetting::active('shipping');
        $this->shipping = [
            'provider' => $shipping?->provider ?? 'flat_rate',
            'environment' => $shipping?->environment ?? 'production',
            'name' => $shipping?->setting('name', 'Entrega local') ?? 'Entrega local',
            'price_cents' => $shipping?->setting('price_cents', config('commerce.shipping.local_delivery_fee_cents', 0))
                ?? config('commerce.shipping.local_delivery_fee_cents', 0),
            'min_days' => $shipping?->setting('min_days', 1) ?? 1,
            'max_days' => $shipping?->setting('max_days', 2) ?? 2,
            'free_shipping_threshold_cents' => $shipping?->setting(
                'free_shipping_threshold_cents',
                config('commerce.shipping.free_shipping_threshold_cents'),
            ) ?? config('commerce.shipping.free_shipping_threshold_cents'),
            'client_id' => '',
            'client_secret' => '',
        ];
        $this->configuredCredentials['shipping'] = ! empty($shipping?->credentials);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function next(StoreSetupManager $setup): void
    {
        $this->validateStep($setup, $this->step);
        $this->step = min(5, $this->step + 1);
    }

    public function previous(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function finish(StoreSetupManager $setup): mixed
    {
        foreach (range(1, 4) as $step) {
            $this->validateStep($setup, $step);
        }

        $payment = $this->paymentCandidate($setup);
        $shipping = $this->shippingCandidate($setup);
        $this->assertConnection('payment.provider', $setup->testPayment($payment));
        $this->assertConnection('shipping.provider', $setup->testShipping($shipping));

        $current = StoreSetting::current();
        $store = $setup->mergeBrandingAssets($this->store, $current->toArray(), [
            'logo' => $this->logo,
            'logo_dark' => $this->logoDark,
            'favicon' => $this->favicon,
            'pwa_icon' => $this->pwaIcon,
        ]);

        $setup->apply(SetupMode::Reconfigure, $store, $this->company, $payment, $shipping);

        Notification::make()->title('Configuração atualizada com segurança.')->success()->send();

        return redirect(static::getUrl());
    }

    public function cancel(): mixed
    {
        return redirect('/admin');
    }

    private function validateStep(StoreSetupManager $setup, int $step): void
    {
        match ($step) {
            1 => $this->validate([
                ...$this->prefixRules($setup->storeRules(withUploads: false), 'store'),
                'logo' => ['nullable', 'image', 'max:4096'],
                'logoDark' => ['nullable', 'image', 'max:4096'],
                'favicon' => ['nullable', 'image', 'max:1024'],
                'pwaIcon' => ['nullable', 'image', 'max:4096'],
            ]),
            2 => $this->validate($this->prefixRules($setup->companyRules(), 'company')),
            3 => $this->validateAndTestPayment($setup),
            4 => $this->validateAndTestShipping($setup),
            default => null,
        };
    }

    private function validateAndTestPayment(StoreSetupManager $setup): void
    {
        Validator::make($this->payment, $setup->paymentRules())->validate();
        $this->assertConnection('payment.provider', $setup->testPayment($this->paymentCandidate($setup)));
    }

    private function validateAndTestShipping(StoreSetupManager $setup): void
    {
        Validator::make($this->shipping, $setup->shippingRules())->validate();
        $this->assertConnection('shipping.provider', $setup->testShipping($this->shippingCandidate($setup)));
    }

    private function paymentCandidate(StoreSetupManager $setup): IntegrationSetting
    {
        return $setup->paymentCandidate($this->payment, Arr::only($this->payment, [
            'public_key', 'access_token', 'webhook_secret', 'api_key', 'webhook_token',
        ]));
    }

    private function shippingCandidate(StoreSetupManager $setup): IntegrationSetting
    {
        return $setup->shippingCandidate($this->shipping, Arr::only($this->shipping, ['client_id', 'client_secret']));
    }

    private function assertConnection(string $field, mixed $result): void
    {
        if (! $result->successful) {
            throw ValidationException::withMessages([$field => $result->message]);
        }
    }

    private function prefixRules(array $rules, string $prefix): array
    {
        return collect($rules)
            ->mapWithKeys(fn (array $rule, string $field): array => ["{$prefix}.{$field}" => $rule])
            ->all();
    }
}
