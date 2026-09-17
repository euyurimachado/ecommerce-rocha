<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuração inicial da loja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <main class="mx-auto max-w-4xl px-4 py-10">
        <header class="mb-8">
            <p class="text-sm font-bold uppercase tracking-widest text-[var(--brand-primary)]">Instalação segura</p>
            <h1 class="mt-2 text-3xl font-black">Configure sua loja</h1>
            <p class="mt-2 text-slate-600">Etapa {{ $step }} de 6. Seus segredos nunca serão exibidos novamente.</p>
            <div class="mt-5 grid grid-cols-6 gap-2" aria-label="Progresso">
                @for ($index = 1; $index <= 6; $index++)
                    <span class="h-2 rounded-full {{ $index <= $step ? 'bg-[var(--brand-primary)]' : 'bg-slate-200' }}"></span>
                @endfor
            </div>
        </header>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('installer.save', ['step' => $step]) }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-6 shadow-sm md:p-8">
            @csrf

            @if ($step === 1)
                <h2 class="text-xl font-black">Criar administrador</h2>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <x-installer-input name="name" label="Nome" :value="old('name', data_get($data, 'admin.name'))" required />
                    <x-installer-input name="email" label="E-mail" type="email" :value="old('email', data_get($data, 'admin.email'))" required />
                    <x-installer-input name="password" label="Senha" type="password" required />
                    <x-installer-input name="password_confirmation" label="Confirmar senha" type="password" required />
                </div>
            @elseif ($step === 2)
                <h2 class="text-xl font-black">Identidade e aparência</h2>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <x-installer-input name="name" label="Nome da loja" :value="old('name', data_get($data, 'store.name'))" required />
                    <x-installer-input name="short_name" label="Nome curto" :value="old('short_name', data_get($data, 'store.short_name'))" required />
                    <x-installer-input name="legal_name" label="Razão social" :value="old('legal_name', data_get($data, 'store.legal_name'))" />
                    <x-installer-input name="slogan" label="Slogan" :value="old('slogan', data_get($data, 'store.slogan'))" />
                    @foreach (['logo' => 'Logo principal', 'logo_dark' => 'Logo alternativa', 'favicon' => 'Favicon', 'pwa_icon' => 'Ícone PWA'] as $name => $label)
                        <label class="block text-sm font-bold text-slate-700">{{ $label }}<input class="mt-2 block w-full rounded-xl border border-slate-300 p-3" type="file" name="{{ $name }}" accept="image/*" @if ($name === 'logo') data-preview-logo @endif></label>
                    @endforeach
                    @foreach (['primary_color' => ['Cor primária','#0098D7'], 'primary_dark_color' => ['Primária escura','#005D8F'], 'secondary_color' => ['Secundária','#A7A9AC'], 'accent_color' => ['Destaque','#F59E0B'], 'background_color' => ['Fundo','#F8FAFC']] as $name => [$label, $default])
                        <label class="block text-sm font-bold text-slate-700">{{ $label }}<input class="mt-2 h-12 w-full rounded-xl border border-slate-300 p-1" type="color" name="{{ $name }}" value="{{ old($name, data_get($data, 'store.'.$name, $default)) }}" data-preview-color="{{ $name }}" required></label>
                    @endforeach
                    <label class="block text-sm font-bold text-slate-700">Fonte principal<select name="font_family" data-preview-font class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-3" required>@foreach ($fonts as $font)<option @selected(old('font_family', data_get($data, 'store.font_family', 'Ubuntu')) === $font)>{{ $font }}</option>@endforeach</select></label>
                </div>
                <div class="mt-8 rounded-2xl border border-slate-200 p-5" data-brand-preview style="font-family: {{ old('font_family', data_get($data, 'store.font_family', 'Ubuntu')) }};background:var(--store-background)">
                    <img data-preview-logo-image class="mb-4 hidden h-12 max-w-48 object-contain" alt="Prévia da logo">
                    <p class="text-sm font-bold text-slate-500">Prévia</p><h3 class="mt-2 text-2xl font-black">Sua marca em destaque</h3>
                    <div class="mt-4 flex items-center gap-4"><button type="button" class="rounded-xl bg-[var(--brand-primary)] px-5 py-3 font-bold text-white">Comprar agora</button><div class="rounded-xl border-l-4 border-[var(--brand-accent)] bg-white p-4">Card de produto</div></div>
                </div>
            @elseif ($step === 3)
                <h2 class="text-xl font-black">Empresa e endereço de origem</h2>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach (['tax_id'=>'CPF/CNPJ','state_registration'=>'Inscrição estadual','email'=>'E-mail','phone'=>'Telefone','whatsapp'=>'WhatsApp','postal_code'=>'CEP','street'=>'Rua','number'=>'Número','complement'=>'Complemento','neighborhood'=>'Bairro','city'=>'Cidade','state'=>'Estado (UF)'] as $name=>$label)
                        <x-installer-input :name="$name" :label="$label" :type="$name === 'email' ? 'email' : 'text'" :value="old($name, data_get($data, 'company.'.$name))" :required="! in_array($name, ['state_registration','whatsapp','complement'])" />
                    @endforeach
                    <input type="hidden" name="country" value="BR">
                </div>
            @elseif ($step === 4)
                <h2 class="text-xl font-black">Pagamentos</h2>
                <p class="mt-2 text-slate-600">Escolha um gateway principal. O teste não cria cobranças.</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label class="block text-sm font-bold">Gateway<select name="provider" class="mt-2 h-12 w-full rounded-xl border px-3" data-provider-select><option value="mercado_pago">Mercado Pago</option><option value="asaas">Asaas</option></select></label>
                    <label class="block text-sm font-bold">Ambiente<select name="environment" class="mt-2 h-12 w-full rounded-xl border px-3"><option value="sandbox">Sandbox (recomendado)</option><option value="production">Produção</option></select></label>
                    <div class="contents" data-provider-fields="mercado_pago"><x-installer-input name="public_key" label="Public Key" /><x-installer-input name="access_token" label="Access Token" type="password" /><x-installer-input name="webhook_secret" label="Webhook secret" type="password" /></div>
                    <div class="contents" data-provider-fields="asaas"><x-installer-input name="api_key" label="API key" type="password" /><x-installer-input name="webhook_token" label="Token do webhook" type="password" /></div>
                </div>
                <p class="mt-5 rounded-xl bg-slate-50 p-4 text-sm">PayPal: em breve. Webhooks: <code>{{ url('/webhooks/payments/mercado-pago') }}</code> e <code>{{ url('/webhooks/payments/asaas') }}</code>.</p>
            @elseif ($step === 5)
                <h2 class="text-xl font-black">Entregas</h2>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label class="block text-sm font-bold">Provider<select name="provider" class="mt-2 h-12 w-full rounded-xl border px-3" data-provider-select><option value="flat_rate">Taxa fixa</option><option value="melhor_envio">Melhor Envio</option></select></label>
                    <label class="block text-sm font-bold">Ambiente<select name="environment" class="mt-2 h-12 w-full rounded-xl border px-3"><option value="sandbox">Sandbox (recomendado)</option><option value="production">Produção</option></select></label>
                    <div class="contents" data-provider-fields="flat_rate"><x-installer-input name="name" label="Nome exibido" value="Entrega local" /><x-installer-input name="price_cents" label="Valor em centavos" type="number" value="990" /><x-installer-input name="min_days" label="Prazo mínimo" type="number" value="1" /><x-installer-input name="max_days" label="Prazo máximo" type="number" value="2" /><x-installer-input name="free_shipping_threshold_cents" label="Grátis acima de (centavos)" type="number" /></div>
                    <div class="contents" data-provider-fields="melhor_envio"><x-installer-input name="client_id" label="Client ID" /><x-installer-input name="client_secret" label="Client Secret" type="password" /></div>
                </div>
                <p class="mt-5 rounded-xl bg-slate-50 p-4 text-sm">Callback OAuth: <code>{{ route('integrations.melhor-envio.callback') }}</code><br>Webhook: <code>{{ route('webhooks.shipping.melhor-envio') }}</code>. Compra automática de etiqueta permanece desativada.</p>
                @if ($melhorEnvioConnected)<p class="mt-4 font-bold text-emerald-700">✓ Melhor Envio conectado</p>@endif
            @else
                <h2 class="text-xl font-black">Sua loja está quase pronta</h2>
                <div class="mt-6 grid gap-3 text-sm font-bold md:grid-cols-2">
                    @foreach (['Administrador'=>'admin','Marca'=>'store','Dados da empresa'=>'company','Pagamento'=>'payment','Entrega'=>'shipping'] as $label=>$key)
                        <p class="rounded-xl border p-4">{{ data_get($data, $key) ? '✓' : '—' }} {{ $label }}</p>
                    @endforeach
                </div>
                <p class="mt-6 text-slate-600">Ao concluir, o instalador público será bloqueado permanentemente.</p>
            @endif

            <div class="mt-8 flex justify-end"><button class="rounded-xl bg-[var(--brand-primary)] px-6 py-3 font-black text-white" type="submit">{{ $step === 6 ? 'Concluir instalação' : 'Salvar e continuar' }}</button></div>
        </form>
    </main>
    <script>
        document.querySelectorAll('[data-provider-select]').forEach(select => {
            const update = () => document.querySelectorAll('[data-provider-fields]').forEach(group => group.hidden = group.dataset.providerFields !== select.value);
            select.addEventListener('change', update); update();
        });
        const preview = document.querySelector('[data-brand-preview]');
        if (preview) {
            const variables = { primary_color: '--brand-primary', primary_dark_color: '--brand-primary-dark', secondary_color: '--brand-secondary', accent_color: '--brand-accent', background_color: '--store-background' };
            document.querySelectorAll('[data-preview-color]').forEach(input => {
                const update = () => preview.style.setProperty(variables[input.dataset.previewColor], input.value);
                input.addEventListener('input', update); update();
            });
            const font = document.querySelector('[data-preview-font]');
            font?.addEventListener('change', () => preview.style.fontFamily = font.value);
            document.querySelector('[data-preview-logo]')?.addEventListener('change', event => {
                const image = document.querySelector('[data-preview-logo-image]');
                const file = event.target.files?.[0];
                if (file) { image.src = URL.createObjectURL(file); image.classList.remove('hidden'); }
            });
        }
    </script>
</body>
</html>
