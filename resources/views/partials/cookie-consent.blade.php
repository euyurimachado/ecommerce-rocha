<div
    id="cookie-consent"
    class="fixed inset-x-0 bottom-16 z-[60] hidden px-4 md:bottom-5"
    data-cookie-consent
    aria-live="polite"
>
    <div class="mx-auto max-w-5xl rounded-lg border border-slate-200 bg-white p-4 shadow-2xl md:flex md:items-center md:gap-5">
        <div class="flex-1">
            <p class="text-sm font-bold text-slate-950">Preferências de cookies</p>
            <p class="mt-1 text-sm text-slate-600">
                Usamos cookies essenciais para a loja funcionar e, com seu consentimento, cookies de análise e marketing para melhorar campanhas, ofertas e atendimento.
            </p>
            <a class="mt-2 inline-flex text-sm font-bold text-rocha-blue" href="{{ route('legal.cookies') }}">Ver política de cookies</a>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-3 md:mt-0 md:w-[28rem]">
            <button class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700" type="button" data-cookie-preferences-open>
                Personalizar
            </button>
            <button class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700" type="button" data-cookie-reject>
                Rejeitar opcionais
            </button>
            <button class="rounded-lg bg-rocha-blue px-4 py-3 text-sm font-bold text-white" type="button" data-cookie-accept-all>
                Aceitar todos
            </button>
        </div>
    </div>
</div>

<div id="cookie-preferences-modal" class="fixed inset-0 z-[70] hidden" data-cookie-modal aria-hidden="true">
    <div class="absolute inset-0 bg-slate-950/60" data-cookie-modal-close></div>
    <section class="absolute inset-x-4 bottom-4 mx-auto max-w-2xl rounded-lg bg-white p-5 shadow-2xl md:bottom-auto md:top-1/2 md:-translate-y-1/2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-lg font-bold text-slate-950 md:text-xl">Central de preferências</p>
                <p class="mt-2 text-sm text-slate-600">Escolha quais categorias podem ser usadas neste dispositivo.</p>
            </div>
            <button class="grid size-10 place-items-center rounded-lg border border-slate-200 font-bold text-slate-600" type="button" data-cookie-modal-close aria-label="Fechar preferências">
                X
            </button>
        </div>

        <div class="mt-5 space-y-3">
            <label class="flex items-start justify-between gap-4 rounded-lg border border-slate-200 p-4">
                <span>
                    <span class="block font-bold text-slate-950">Essenciais</span>
                    <span class="mt-1 block text-sm text-slate-600">Carrinho, sessão, segurança, checkout e preferências obrigatórias da loja.</span>
                </span>
                <input class="mt-1" type="checkbox" checked disabled>
            </label>

            <label class="flex items-start justify-between gap-4 rounded-lg border border-slate-200 p-4">
                <span>
                    <span class="block font-bold text-slate-950">Analíticos</span>
                    <span class="mt-1 block text-sm text-slate-600">Medição de visitas, buscas, produtos vistos e funil de compra.</span>
                </span>
                <input class="mt-1" type="checkbox" data-cookie-category="analytics">
            </label>

            <label class="flex items-start justify-between gap-4 rounded-lg border border-slate-200 p-4">
                <span>
                    <span class="block font-bold text-slate-950">Marketing</span>
                    <span class="mt-1 block text-sm text-slate-600">Campanhas, remarketing, ofertas personalizadas, WhatsApp e mídia paga.</span>
                </span>
                <input class="mt-1" type="checkbox" data-cookie-category="marketing">
            </label>
        </div>

        <div class="mt-5 grid gap-2 sm:grid-cols-3">
            <button class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700" type="button" data-cookie-reject>
                Rejeitar opcionais
            </button>
            <button class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700" type="button" data-cookie-save>
                Salvar escolhas
            </button>
            <button class="rounded-lg bg-rocha-blue px-4 py-3 text-sm font-bold text-white" type="button" data-cookie-accept-all>
                Aceitar todos
            </button>
        </div>
    </section>
</div>
