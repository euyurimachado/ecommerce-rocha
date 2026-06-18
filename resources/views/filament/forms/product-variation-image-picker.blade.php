@php
    $statePath = $getStatePath();
    $selectedPath = $getState();
    $selectedImage = collect($images)->firstWhere('path', $selectedPath);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ open: false }"
        class="space-y-3"
    >
        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                x-on:click="open = true"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary-500 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
            >
                Selecionar da biblioteca
            </button>

            @if ($selectedPath)
                <button
                    type="button"
                    wire:click="$set('{{ $statePath }}', null)"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:border-danger-500 hover:text-danger-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                >
                    Remover imagem
                </button>
            @endif
        </div>

        @if ($selectedPath)
            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-md bg-white ring-1 ring-gray-200 dark:bg-gray-950 dark:ring-gray-800">
                    <img
                        class="h-full w-full object-contain"
                        src="{{ $selectedImage['url'] ?? asset('storage/'.$selectedPath) }}"
                        alt=""
                    >
                </div>
                <div class="min-w-0 text-sm">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Imagem selecionada</p>
                    <p class="truncate text-gray-500 dark:text-gray-400">{{ $selectedImage['filename'] ?? basename($selectedPath) }}</p>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma imagem vinculada. A opção usará a imagem principal do produto.</p>
        @endif

        <div
            x-cloak
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/55 p-4"
            x-on:keydown.escape.window="open = false"
        >
            <div
                x-on:click.outside="open = false"
                class="max-h-[85vh] w-full max-w-5xl overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Biblioteca de imagens</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Escolha uma imagem já enviada em produtos.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                    >
                        Fechar
                    </button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-5">
                    @if (count($images))
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            @foreach ($images as $image)
                                <button
                                    type="button"
                                    wire:click="$set('{{ $statePath }}', @js($image['path']))"
                                    x-on:click="open = false"
                                    class="{{ $selectedPath === $image['path'] ? 'border-primary-500 ring-2 ring-primary-500/30' : 'border-gray-200 dark:border-gray-800' }} group overflow-hidden rounded-lg border bg-white text-left transition hover:border-primary-500 dark:bg-gray-950"
                                >
                                    <span class="grid aspect-square place-items-center bg-gray-50 p-2 dark:bg-gray-900">
                                        <img
                                            class="h-full w-full object-contain transition group-hover:scale-105"
                                            src="{{ $image['url'] }}"
                                            alt=""
                                            loading="lazy"
                                        >
                                    </span>
                                    <span class="block min-w-0 p-2">
                                        <span class="block truncate text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $image['filename'] }}</span>
                                        <span class="block truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $image['label'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Nenhuma imagem de produto encontrada ainda. Use o campo de upload abaixo para anexar uma nova imagem.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
