@props([
    'label',
    'statePath',
    'required' => false,
    'hint' => null,
])

<x-filament-forms::field-wrapper
    :label="$label"
    :state-path="$statePath"
    :required="$required"
    {{ $attributes }}
>
    {{ $slot }}

    @if ($hint)
        <p class="mt-1.5 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</x-filament-forms::field-wrapper>
