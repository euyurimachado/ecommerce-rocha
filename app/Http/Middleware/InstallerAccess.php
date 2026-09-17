<?php

namespace App\Http\Middleware;

use App\Models\StoreSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (StoreSetting::installationDetected()) {
            abort(404);
        }

        $configured = (string) config('installer.token');

        if ($configured === '') {
            abort(503, 'O instalador não está habilitado. Configure INSTALLER_TOKEN.');
        }

        $provided = (string) ($request->query('token') ?: $request->input('installer_token'));

        if ($provided !== '' && hash_equals($configured, $provided)) {
            $request->session()->put('installer_authorized', true);
        }

        abort_unless((bool) $request->session()->get('installer_authorized'), 403);

        return $next($request);
    }
}
