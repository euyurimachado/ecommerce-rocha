<?php

namespace App\Http\Controllers;

use App\Enums\SetupMode;
use App\Models\IntegrationSetting;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\Setup\StoreSetupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstallerController extends Controller
{
    public function __construct(private readonly StoreSetupManager $setup) {}

    public function show(Request $request, int $step = 1): View
    {
        abort_unless($step >= 1 && $step <= 6, 404);

        return view('installer.wizard', [
            'step' => $step,
            'data' => $request->session()->get('installer.data', []),
            'fonts' => StoreSetting::FONTS,
            'melhorEnvioConnected' => IntegrationSetting::query()
                ->where('type', 'shipping')->where('provider', 'melhor_envio')
                ->whereNotNull('connected_at')->exists(),
        ]);
    }

    public function save(Request $request, int $step): RedirectResponse
    {
        abort_unless($step >= 1 && $step <= 6, 404);
        $data = $request->session()->get('installer.data', []);

        match ($step) {
            1 => $data['admin'] = $this->adminData($request),
            2 => $data['store'] = $this->storeData($request, $data['store'] ?? []),
            3 => $data['company'] = $this->companyData($request),
            4 => $data['payment'] = $this->paymentData($request),
            5 => $data['shipping'] = $this->shippingData($request),
            6 => $this->finish($request, $data),
        };

        if ($step === 6) {
            return redirect('/admin')->with('status', 'Sua loja está pronta.');
        }

        $request->session()->put('installer.data', $data);

        if ($step === 5 && data_get($data, 'shipping.provider') === 'melhor_envio') {
            $connected = IntegrationSetting::query()
                ->where('type', 'shipping')->where('provider', 'melhor_envio')
                ->whereNotNull('connected_at')->exists();

            if (! $connected) {
                return redirect()->route('integrations.melhor-envio.redirect');
            }
        }

        return redirect()->route('installer.show', ['step' => $step + 1]);
    }

    private function adminData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'min:10', 'max:255'],
        ]);
        $validated['password'] = Hash::make($validated['password']);

        return $validated;
    }

    private function storeData(Request $request, array $current): array
    {
        $validated = $request->validate($this->setup->storeRules());
        $uploads = collect(['logo', 'logo_dark', 'favicon', 'pwa_icon'])
            ->mapWithKeys(fn (string $asset): array => [$asset => $request->file($asset)])
            ->all();

        return $this->setup->mergeBrandingAssets($validated, $current, $uploads);
    }

    private function companyData(Request $request): array
    {
        return $request->validate($this->setup->companyRules());
    }

    private function paymentData(Request $request): array
    {
        $validated = $request->validate($this->setup->paymentRules(requireCredentials: true));
        $credentials = array_filter([
            'public_key' => $validated['public_key'] ?? null,
            'access_token' => $validated['access_token'] ?? null,
            'webhook_secret' => $validated['webhook_secret'] ?? null,
            'api_key' => $validated['api_key'] ?? null,
            'webhook_token' => $validated['webhook_token'] ?? null,
        ], fn ($value) => filled($value));
        $integration = $this->setup->paymentCandidate($validated, $credentials);
        $result = $this->setup->testPayment($integration);

        if (! $result->successful) {
            throw ValidationException::withMessages(['provider' => $result->message]);
        }

        return [
            'provider' => $validated['provider'],
            'environment' => $validated['environment'],
            'credentials' => Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR)),
        ];
    }

    private function shippingData(Request $request): array
    {
        $validated = $request->validate($this->setup->shippingRules(requireCredentials: true));
        $credentials = array_filter([
            'client_id' => $validated['client_id'] ?? null,
            'client_secret' => $validated['client_secret'] ?? null,
        ], fn ($value) => filled($value));
        $settings = array_filter([
            'name' => $validated['name'] ?? null,
            'price_cents' => $validated['price_cents'] ?? null,
            'min_days' => $validated['min_days'] ?? null,
            'max_days' => $validated['max_days'] ?? null,
            'free_shipping_threshold_cents' => $validated['free_shipping_threshold_cents'] ?? null,
            'auto_purchase_label' => false,
        ], fn ($value) => $value !== null);

        if ($validated['provider'] === 'melhor_envio') {
            IntegrationSetting::query()->updateOrCreate(
                ['type' => 'shipping', 'provider' => 'melhor_envio'],
                ['enabled' => false, 'environment' => $validated['environment'], 'credentials' => $credentials, 'settings' => $settings],
            );
        }

        return [
            'provider' => $validated['provider'], 'environment' => $validated['environment'],
            'credentials' => Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR)),
            'settings' => $settings,
        ];
    }

    private function finish(Request $request, array $data): void
    {
        foreach (['admin', 'store', 'company', 'payment', 'shipping'] as $required) {
            abort_unless(isset($data[$required]), 422, 'Conclua todas as etapas do instalador.');
        }

        $payment = $this->setup->paymentCandidate($data['payment'], $this->decryptCredentials($data['payment']));
        $shipping = $this->setup->shippingCandidate($data['shipping'], $this->decryptCredentials($data['shipping']));

        foreach ([['payment', $this->setup->testPayment($payment)], ['shipping', $this->setup->testShipping($shipping)]] as [$field, $result]) {
            if (! $result->successful) {
                throw ValidationException::withMessages([$field => $result->message]);
            }
        }

        $this->setup->apply(
            SetupMode::InitialInstall,
            $data['store'],
            $data['company'],
            $payment,
            $shipping,
            fn () => User::query()->create($data['admin'] + ['role' => User::ROLE_ADMIN, 'is_active' => true]),
        );

        $request->session()->forget(['installer.data', 'installer_authorized']);
    }

    private function decryptCredentials(array $data): array
    {
        return json_decode(Crypt::decryptString($data['credentials']), true, flags: JSON_THROW_ON_ERROR);
    }
}
