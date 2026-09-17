<x-filament-panels::page>
    @php
        $steps = [
            ['label' => 'Loja', 'description' => 'Marca e aparência'],
            ['label' => 'Empresa', 'description' => 'Contato e endereço'],
            ['label' => 'Pagamento', 'description' => 'Gateway e credenciais'],
            ['label' => 'Entrega', 'description' => 'Frete e prazos'],
            ['label' => 'Revisão', 'description' => 'Validar e aplicar'],
        ];
    @endphp

    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-r from-primary-50 via-white to-white p-5 shadow-sm dark:border-primary-900 dark:from-primary-950 dark:via-gray-900 dark:to-gray-900 sm:p-6">
            <div class="flex items-start gap-4">
                <div class="hidden size-11 shrink-0 place-items-center rounded-xl bg-primary-600 text-white shadow-sm sm:grid">
                    <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="size-6" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Configuração guiada e segura</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Revise os dados atuais da loja por etapas. Nada é gravado antes da confirmação final, e credenciais vazias mantêm os valores já configurados.
                    </p>
                </div>
            </div>
        </div>

        <nav class="overflow-x-auto pb-2" aria-label="Progresso da reconfiguração">
            <ol class="flex min-w-[48rem] gap-3 lg:min-w-0">
                @foreach ($steps as $index => $wizardStep)
                    @php
                        $number = $index + 1;
                        $isCurrent = $step === $number;
                        $isComplete = $step > $number;
                    @endphp
                    <li class="min-w-0 flex-1">
                        <div @class([
                            'flex h-full items-center gap-3 rounded-xl border px-4 py-3 transition',
                            'border-primary-500 bg-primary-50 shadow-sm ring-1 ring-primary-500/20 dark:bg-primary-950' => $isCurrent,
                            'border-success-200 bg-success-50/70 dark:border-success-900 dark:bg-success-950/60' => $isComplete,
                            'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => ! $isCurrent && ! $isComplete,
                        ]) aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                            <span @class([
                                'grid size-9 shrink-0 place-items-center rounded-full text-sm font-bold',
                                'bg-primary-600 text-white' => $isCurrent,
                                'bg-success-600 text-white' => $isComplete,
                                'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300' => ! $isCurrent && ! $isComplete,
                            ])>
                                @if ($isComplete)
                                    <x-filament::icon icon="heroicon-m-check" class="size-5" />
                                @else
                                    {{ $number }}
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $wizardStep['label'] }}</span>
                                <span class="mt-0.5 block truncate text-xs text-gray-500 dark:text-gray-400">{{ $wizardStep['description'] }}</span>
                            </span>
                        </div>
                    </li>
                @endforeach
            </ol>
        </nav>

        <form wire:submit="finish" class="space-y-6">
            @if ($step === 1)
                <x-filament::section>
                    <x-slot name="heading">Identidade da loja</x-slot>
                    <x-slot name="description">Nome, comunicação visual e elementos usados no site, painel e PWA.</x-slot>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <x-filament.setup-field label="Nome da loja" state-path="store.name" required>
                            <x-filament::input.wrapper :valid="! $errors->has('store.name')">
                                <x-filament::input id="store-name" wire:model="store.name" placeholder="Ex.: Rocha Sports" />
                            </x-filament::input.wrapper>
                        </x-filament.setup-field>
                        <x-filament.setup-field label="Nome curto" state-path="store.short_name" required hint="Usado onde o espaço é reduzido, como no aplicativo instalado.">
                            <x-filament::input.wrapper :valid="! $errors->has('store.short_name')">
                                <x-filament::input id="store-short-name" wire:model="store.short_name" placeholder="Ex.: Rocha" />
                            </x-filament::input.wrapper>
                        </x-filament.setup-field>
                        <x-filament.setup-field label="Razão social" state-path="store.legal_name">
                            <x-filament::input.wrapper :valid="! $errors->has('store.legal_name')">
                                <x-filament::input wire:model="store.legal_name" placeholder="Nome empresarial" />
                            </x-filament::input.wrapper>
                        </x-filament.setup-field>
                        <x-filament.setup-field label="Slogan" state-path="store.slogan">
                            <x-filament::input.wrapper :valid="! $errors->has('store.slogan')">
                                <x-filament::input wire:model="store.slogan" placeholder="Uma frase curta sobre a loja" />
                            </x-filament::input.wrapper>
                        </x-filament.setup-field>
                    </div>

                    <div class="my-8 border-t border-gray-200 dark:border-gray-700"></div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Paleta e tipografia</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cores aplicadas aos botões, destaques e elementos de navegação da loja.</p>
                    </div>
                    <div class="mt-5 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach (['primary_color' => 'Cor primária', 'primary_dark_color' => 'Primária escura', 'secondary_color' => 'Cor secundária', 'accent_color' => 'Cor de destaque', 'background_color' => 'Cor de fundo'] as $field => $label)
                            <x-filament.setup-field :label="$label" :state-path="'store.'.$field" required>
                                <div class="flex items-center gap-3">
                                    <x-filament::input.wrapper class="w-20 shrink-0" :valid="! $errors->has('store.'.$field)">
                                        <x-filament::input class="h-11 cursor-pointer p-1" type="color" wire:model.live="store.{{ $field }}" />
                                    </x-filament::input.wrapper>
                                    <x-filament::input.wrapper class="min-w-0 flex-1" :valid="! $errors->has('store.'.$field)">
                                        <x-filament::input wire:model="store.{{ $field }}" placeholder="#000000" />
                                    </x-filament::input.wrapper>
                                </div>
                            </x-filament.setup-field>
                        @endforeach
                        <x-filament.setup-field label="Fonte principal" state-path="store.font_family" required>
                            <x-filament::input.wrapper :valid="! $errors->has('store.font_family')">
                                <x-filament::input.select wire:model="store.font_family">
                                    @foreach (\App\Models\StoreSetting::FONTS as $font)
                                        <option value="{{ $font }}">{{ $font }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </x-filament.setup-field>
                    </div>

                    <div class="my-8 border-t border-gray-200 dark:border-gray-700"></div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Arquivos da marca</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Envie somente os arquivos que deseja substituir. Os demais serão preservados.</p>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ([
                            ['property' => 'logo', 'label' => 'Logo principal', 'hint' => 'PNG, JPG, WebP ou SVG. Até 4 MB.'],
                            ['property' => 'logoDark', 'label' => 'Logo alternativa', 'hint' => 'Versão para fundos escuros. Até 4 MB.'],
                            ['property' => 'favicon', 'label' => 'Favicon', 'hint' => 'Imagem quadrada. Até 1 MB.'],
                            ['property' => 'pwaIcon', 'label' => 'Ícone PWA', 'hint' => 'Preferencialmente 512 × 512 px. Até 4 MB.'],
                        ] as $upload)
                            <label class="group block cursor-pointer rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 transition hover:border-primary-400 hover:bg-primary-50/50 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-primary-600">
                                <span class="flex items-start gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-primary-600 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">
                                        <x-filament::icon icon="heroicon-o-arrow-up-tray" class="size-5" />
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-950 dark:text-white">{{ $upload['label'] }}</span>
                                        <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $upload['hint'] }}</span>
                                    </span>
                                </span>
                                <input class="mt-4 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-500 dark:text-gray-300" type="file" wire:model="{{ $upload['property'] }}" accept="image/*">
                            </label>
                        @endforeach
                    </div>
                </x-filament::section>
            @elseif ($step === 2)
                <x-filament::section>
                    <x-slot name="heading">Dados da empresa</x-slot>
                    <x-slot name="description">Informações usadas em documentos, atendimento e cálculo de origem das entregas.</x-slot>
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ([
                            ['tax_id', 'CPF ou CNPJ', true, '00.000.000/0000-00'],
                            ['state_registration', 'Inscrição estadual', false, 'Opcional'],
                            ['email', 'E-mail de atendimento', true, 'contato@sualoja.com.br'],
                            ['phone', 'Telefone', true, '(00) 0000-0000'],
                            ['whatsapp', 'WhatsApp', false, '(00) 00000-0000'],
                            ['postal_code', 'CEP de origem', true, '00000-000'],
                        ] as [$field, $label, $required, $placeholder])
                            <x-filament.setup-field :label="$label" :state-path="'company.'.$field" :required="$required">
                                <x-filament::input.wrapper :valid="! $errors->has('company.'.$field)">
                                    <x-filament::input wire:model="company.{{ $field }}" :placeholder="$placeholder" />
                                </x-filament::input.wrapper>
                            </x-filament.setup-field>
                        @endforeach
                        <x-filament.setup-field class="md:col-span-2" label="Rua / Logradouro" state-path="company.street" required>
                            <x-filament::input.wrapper :valid="! $errors->has('company.street')">
                                <x-filament::input wire:model="company.street" placeholder="Rua, avenida ou estrada" />
                            </x-filament::input.wrapper>
                        </x-filament.setup-field>
                        @foreach ([
                            ['number', 'Número', true, '123'],
                            ['complement', 'Complemento', false, 'Sala, loja, bloco...'],
                            ['neighborhood', 'Bairro', true, 'Bairro'],
                            ['city', 'Cidade', true, 'Cidade'],
                            ['state', 'UF', true, 'RJ'],
                            ['country', 'País', true, 'BR'],
                        ] as [$field, $label, $required, $placeholder])
                            <x-filament.setup-field :label="$label" :state-path="'company.'.$field" :required="$required">
                                <x-filament::input.wrapper :valid="! $errors->has('company.'.$field)">
                                    <x-filament::input wire:model="company.{{ $field }}" :placeholder="$placeholder" />
                                </x-filament::input.wrapper>
                            </x-filament.setup-field>
                        @endforeach
                    </div>
                </x-filament::section>
            @elseif ($step === 3)
                <x-filament::section>
                    <x-slot name="heading">Pagamento</x-slot>
                    <x-slot name="description">Escolha o gateway, revise o ambiente e rotacione apenas as credenciais necessárias.</x-slot>
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-lg bg-white text-primary-600 shadow-sm dark:bg-gray-900"><x-filament::icon icon="heroicon-o-shield-check" class="size-5" /></span>
                            <div><p class="text-sm font-semibold text-gray-950 dark:text-white">Credenciais protegidas</p><p class="text-xs text-gray-500 dark:text-gray-400">Segredos atuais nunca são enviados ao navegador.</p></div>
                        </div>
                        <x-filament::badge :color="$configuredCredentials['payment'] ? 'success' : 'warning'">{{ $configuredCredentials['payment'] ? 'Credenciais configuradas' : 'Configuração pendente' }}</x-filament::badge>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <x-filament.setup-field label="Gateway de pagamento" state-path="payment.provider" required>
                            <x-filament::input.wrapper :valid="! $errors->has('payment.provider')"><x-filament::input.select wire:model.live="payment.provider"><option value="mercado_pago">Mercado Pago</option><option value="asaas">Asaas</option></x-filament::input.select></x-filament::input.wrapper>
                        </x-filament.setup-field>
                        <x-filament.setup-field label="Ambiente" state-path="payment.environment" required hint="Use produção somente com credenciais reais.">
                            <x-filament::input.wrapper :valid="! $errors->has('payment.environment')"><x-filament::input.select wire:model="payment.environment"><option value="sandbox">Sandbox / testes</option><option value="production">Produção</option></x-filament::input.select></x-filament::input.wrapper>
                        </x-filament.setup-field>
                        <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700 lg:col-span-2">
                            <div class="mb-5"><h3 class="text-sm font-semibold text-gray-950 dark:text-white">Rotacionar credenciais</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Deixe um campo vazio para preservar o valor existente.</p></div>
                            <div class="grid gap-6 lg:grid-cols-2">
                                @if ($payment['provider'] === 'mercado_pago')
                                    <x-filament.setup-field label="Public Key" state-path="payment.public_key" hint="Preencha somente para substituir a chave atual."><x-filament::input.wrapper :valid="! $errors->has('payment.public_key')"><x-filament::input wire:model="payment.public_key" autocomplete="off" placeholder="Nova Public Key" /></x-filament::input.wrapper></x-filament.setup-field>
                                    <x-filament.setup-field label="Access Token" state-path="payment.access_token"><x-filament::input.wrapper :valid="! $errors->has('payment.access_token')"><x-filament::input type="password" wire:model="payment.access_token" autocomplete="new-password" placeholder="Novo Access Token" /></x-filament::input.wrapper></x-filament.setup-field>
                                    <x-filament.setup-field class="lg:col-span-2" label="Webhook Secret" state-path="payment.webhook_secret"><x-filament::input.wrapper :valid="! $errors->has('payment.webhook_secret')"><x-filament::input type="password" wire:model="payment.webhook_secret" autocomplete="new-password" placeholder="Novo segredo do webhook" /></x-filament::input.wrapper></x-filament.setup-field>
                                @else
                                    <x-filament.setup-field label="API Key" state-path="payment.api_key"><x-filament::input.wrapper :valid="! $errors->has('payment.api_key')"><x-filament::input type="password" wire:model="payment.api_key" autocomplete="new-password" placeholder="Nova API Key" /></x-filament::input.wrapper></x-filament.setup-field>
                                    <x-filament.setup-field label="Token do webhook" state-path="payment.webhook_token"><x-filament::input.wrapper :valid="! $errors->has('payment.webhook_token')"><x-filament::input type="password" wire:model="payment.webhook_token" autocomplete="new-password" placeholder="Novo token do webhook" /></x-filament::input.wrapper></x-filament.setup-field>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @elseif ($step === 4)
                <x-filament::section>
                    <x-slot name="heading">Entrega e frete</x-slot>
                    <x-slot name="description">Configure o cálculo exibido ao cliente. O provider será testado antes da ativação.</x-slot>
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-lg bg-white text-primary-600 shadow-sm dark:bg-gray-900"><x-filament::icon icon="heroicon-o-truck" class="size-5" /></span><div><p class="text-sm font-semibold text-gray-950 dark:text-white">Validação antes da troca</p><p class="text-xs text-gray-500 dark:text-gray-400">O método atual permanece ativo se o teste falhar.</p></div></div>
                        <x-filament::badge :color="$configuredCredentials['shipping'] ? 'success' : 'gray'">{{ $configuredCredentials['shipping'] ? 'Integração configurada' : 'Sem credenciais externas' }}</x-filament::badge>
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <x-filament.setup-field label="Método de entrega" state-path="shipping.provider" required><x-filament::input.wrapper :valid="! $errors->has('shipping.provider')"><x-filament::input.select wire:model.live="shipping.provider"><option value="flat_rate">Taxa fixa / entrega local</option><option value="melhor_envio">Melhor Envio</option></x-filament::input.select></x-filament::input.wrapper></x-filament.setup-field>
                        <x-filament.setup-field label="Ambiente" state-path="shipping.environment" required><x-filament::input.wrapper :valid="! $errors->has('shipping.environment')"><x-filament::input.select wire:model="shipping.environment"><option value="sandbox">Sandbox / testes</option><option value="production">Produção</option></x-filament::input.select></x-filament::input.wrapper></x-filament.setup-field>
                        @if ($shipping['provider'] === 'flat_rate')
                            <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700 lg:col-span-2">
                                <div class="mb-5"><h3 class="text-sm font-semibold text-gray-950 dark:text-white">Condições da entrega local</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Valores monetários são informados em centavos.</p></div>
                                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach ([['name', 'Nome exibido', 'Ex.: Entrega local'], ['price_cents', 'Valor em centavos', 'Ex.: 990'], ['min_days', 'Prazo mínimo', 'Ex.: 1'], ['max_days', 'Prazo máximo', 'Ex.: 2'], ['free_shipping_threshold_cents', 'Frete grátis acima de', 'Ex.: 25000']] as [$field, $label, $placeholder])
                                        <x-filament.setup-field :label="$label" :state-path="'shipping.'.$field"><x-filament::input.wrapper :valid="! $errors->has('shipping.'.$field)"><x-filament::input wire:model="shipping.{{ $field }}" :placeholder="$placeholder" /></x-filament::input.wrapper></x-filament.setup-field>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700 lg:col-span-2">
                                <div class="mb-5"><h3 class="text-sm font-semibold text-gray-950 dark:text-white">Credenciais do Melhor Envio</h3><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Deixe em branco para preservar os valores existentes.</p></div>
                                <div class="grid gap-6 lg:grid-cols-2">
                                    <x-filament.setup-field label="Client ID" state-path="shipping.client_id"><x-filament::input.wrapper :valid="! $errors->has('shipping.client_id')"><x-filament::input wire:model="shipping.client_id" autocomplete="off" placeholder="Novo Client ID" /></x-filament::input.wrapper></x-filament.setup-field>
                                    <x-filament.setup-field label="Client Secret" state-path="shipping.client_secret"><x-filament::input.wrapper :valid="! $errors->has('shipping.client_secret')"><x-filament::input type="password" wire:model="shipping.client_secret" autocomplete="new-password" placeholder="Novo Client Secret" /></x-filament::input.wrapper></x-filament.setup-field>
                                </div>
                                <div class="mt-5 flex gap-3 rounded-lg bg-warning-50 p-4 text-sm leading-6 text-warning-800 dark:bg-warning-950 dark:text-warning-200"><x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 size-5 shrink-0" /><p>Se esta conta ainda não foi autorizada, conecte-a primeiro em <strong>Pagamentos e entregas</strong>. O assistente não ativa uma conexão inválida.</p></div>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @else
                <x-filament::section>
                    <x-slot name="heading">Revisar e aplicar</x-slot>
                    <x-slot name="description">Confira o resumo. As conexões serão testadas novamente antes de qualquer alteração ser salva.</x-slot>
                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach ([
                            ['icon' => 'heroicon-o-building-storefront', 'label' => 'Loja', 'value' => $store['name']],
                            ['icon' => 'heroicon-o-credit-card', 'label' => 'Pagamento', 'value' => str($payment['provider'])->replace('_', ' ')->title().' · '.$payment['environment']],
                            ['icon' => 'heroicon-o-truck', 'label' => 'Entrega', 'value' => str($shipping['provider'])->replace('_', ' ')->title().' · '.$shipping['environment']],
                        ] as $summary)
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
                                <span class="grid size-10 place-items-center rounded-lg bg-white text-primary-600 shadow-sm dark:bg-gray-900"><x-filament::icon :icon="$summary['icon']" class="size-5" /></span>
                                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $summary['label'] }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $summary['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6 flex gap-3 rounded-xl border border-success-200 bg-success-50 p-4 text-sm leading-6 text-success-800 dark:border-success-900 dark:bg-success-950 dark:text-success-200"><x-filament::icon icon="heroicon-o-shield-check" class="mt-0.5 size-5 shrink-0" /><p>As alterações serão gravadas em uma única transação. Se um provider falhar no teste, a configuração atualmente ativa será mantida.</p></div>
                </x-filament::section>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-900 dark:bg-danger-950" role="alert">
                    <div class="flex gap-3"><x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-danger-600" /><div><p class="text-sm font-semibold text-danger-800 dark:text-danger-200">Revise os campos destacados</p><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-danger-700 dark:text-danger-300">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
                </div>
            @endif

            <div class="sticky bottom-4 z-10 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-xl shadow-gray-950/5 backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-filament::button type="button" color="gray" outlined wire:click="cancel">Cancelar</x-filament::button>
                    <div class="flex items-center gap-3">
                        @if ($step > 1)<x-filament::button type="button" color="gray" wire:click="previous" icon="heroicon-m-arrow-left">Voltar</x-filament::button>@endif
                        @if ($step < 5)
                            <x-filament::button type="button" wire:click="next" wire:loading.attr="disabled" wire:target="next" icon-position="after" icon="heroicon-m-arrow-right">Continuar</x-filament::button>
                        @else
                            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="finish" icon="heroicon-m-check">Testar e aplicar</x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>
