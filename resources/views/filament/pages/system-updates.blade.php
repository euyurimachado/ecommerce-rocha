<x-filament-panels::page>
    @php
        $statusLabels = [
            'available' => 'Atualização disponível',
            'incompatible' => 'Atualização incompatível',
            'current' => 'Sistema atualizado',
            'not_configured' => 'Fonte não configurada',
            'unavailable' => 'Consulta indisponível',
        ];
        $statusColors = [
            'available' => 'warning',
            'incompatible' => 'danger',
            'current' => 'success',
            'not_configured' => 'gray',
            'unavailable' => 'danger',
        ];
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Versão instalada</x-slot>
            <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ $release['installed'] }}</p>
            <p class="mt-2 text-sm text-gray-500">Fonte: {{ $release['source'] }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Versão disponível</x-slot>
            <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ $release['available'] ?: '—' }}</p>
            <p class="mt-2 text-sm text-gray-500">Canal {{ $release['channel'] }}{{ $release['released_at'] ? ' · '.$release['released_at'] : '' }}</p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Status</x-slot>
            <x-filament::badge :color="$statusColors[$release['status']] ?? 'gray'">
                {{ $statusLabels[$release['status']] ?? $release['status'] }}
            </x-filament::badge>
            @if ($release['message'])
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $release['message'] }}</p>
            @endif
        </x-filament::section>
    </div>

    @if ($release['available'])
        <x-filament::section>
            <x-slot name="heading">Release {{ $release['available'] }}</x-slot>
            <x-slot name="description">Esta tela é somente informativa. A aplicação não altera os próprios arquivos e não executa comandos recebidos do manifesto.</x-slot>

            <div class="flex flex-wrap gap-2">
                @if ($release['security'])
                    <x-filament::badge color="danger">Segurança</x-filament::badge>
                @endif
                @if ($release['breaking'])
                    <x-filament::badge color="warning">Breaking change</x-filament::badge>
                @endif
                <x-filament::badge :color="$release['compatible'] ? 'success' : 'danger'">
                    {{ $release['compatible'] ? 'Compatível' : 'Incompatível' }}
                </x-filament::badge>
            </div>

            @foreach ($release['compatibility_reasons'] as $reason)
                <p class="mt-3 text-sm text-danger-600">{{ $reason }}</p>
            @endforeach

            <div class="mt-5 whitespace-pre-line text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $release['release_notes'] }}</div>
            <p class="mt-5 break-all font-mono text-xs text-gray-500">SHA-256: {{ $release['checksum'] }}</p>
        </x-filament::section>
    @endif

    <div>
        <x-filament::button wire:click="refreshRelease" wire:loading.attr="disabled">
            Consultar novamente
        </x-filament::button>
    </div>
</x-filament-panels::page>
