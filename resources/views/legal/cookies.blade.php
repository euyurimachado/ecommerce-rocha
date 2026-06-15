@extends('layouts.storefront')

@section('title', 'Política de Cookies | Rocha Sports')
@section('meta_description', 'Política de cookies da Rocha Sports com categorias essenciais, analíticas e marketing, além da central de preferências LGPD.')

@section('content')
    <article class="mx-auto max-w-4xl px-4 py-10 lg:px-6">
        <p class="text-sm font-semibold text-rocha-blue">Preferências</p>
        <h1 class="mt-2 text-2xl font-bold leading-snug md:text-3xl">Política de Cookies</h1>
        <p class="mt-4 text-slate-600">A Rocha Sports usa cookies e tecnologias semelhantes para manter a loja funcionando, proteger a compra e, quando autorizado, medir desempenho e personalizar campanhas.</p>

        <button class="mt-6 rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white" type="button" data-cookie-preferences-open>
            Gerenciar preferências
        </button>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <section class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-bold text-slate-950">Essenciais</h2>
                <p class="mt-2 text-sm text-slate-600">Necessários para carrinho, sessão, checkout, segurança, prevenção de fraude e preferências obrigatórias. Não podem ser desativados pela central.</p>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-bold text-slate-950">Analíticos</h2>
                <p class="mt-2 text-sm text-slate-600">Ajudam a entender visitas, buscas, categorias acessadas, produtos vistos e etapas do funil de compra para melhorar a loja.</p>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-bold text-slate-950">Marketing</h2>
                <p class="mt-2 text-sm text-slate-600">Permitem campanhas, remarketing, ofertas personalizadas, integração com mídia paga e comunicação promocional autorizada.</p>
            </section>
        </div>

        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-5 text-slate-600">
            <h2 class="text-lg font-bold text-slate-950 md:text-xl">Registro de consentimento</h2>
            <p class="mt-2">As preferências ficam salvas neste dispositivo e podem ser alteradas a qualquer momento pela central de preferências no rodapé ou nesta página.</p>
            <p class="mt-2">Antes de ativar ferramentas externas de analytics ou marketing, a plataforma deve consultar o consentimento salvo e carregar apenas os scripts permitidos.</p>
        </section>
    </article>
@endsection
