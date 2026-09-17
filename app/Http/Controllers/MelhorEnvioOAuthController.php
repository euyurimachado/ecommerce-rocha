<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\Shipping\MelhorEnvio\MelhorEnvioClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MelhorEnvioOAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $this->authorize($request);
        $integration = $this->integration();
        $state = Str::random(64);
        $request->session()->put('melhor_envio_oauth_state', hash('sha256', $state));

        return redirect()->away((new MelhorEnvioClient($integration))->authorizeUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->authorize($request);
        abort_unless(
            filled($request->query('state'))
            && hash_equals((string) $request->session()->pull('melhor_envio_oauth_state'), hash('sha256', (string) $request->query('state'))),
            403,
        );

        $request->validate(['code' => ['required', 'string']]);
        $integration = $this->integration();
        $client = new MelhorEnvioClient($integration);
        $client->storeTokens($client->exchangeCode((string) $request->query('code')));

        if ((bool) $request->session()->get('installer_authorized')) {
            return redirect()->route('installer.show', ['step' => 6])
                ->with('status', 'Melhor Envio conectado com sucesso.');
        }

        return redirect('/admin')->with('status', 'Melhor Envio conectado com sucesso.');
    }

    private function integration(): IntegrationSetting
    {
        return IntegrationSetting::query()
            ->where('type', 'shipping')->where('provider', 'melhor_envio')->firstOrFail();
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->canAccessPanel(filament()->getPanel('admin'))
            || (bool) $request->session()->get('installer_authorized'), 403);
    }
}
