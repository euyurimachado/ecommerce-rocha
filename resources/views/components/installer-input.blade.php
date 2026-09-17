@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])
<label class="block text-sm font-bold text-slate-700">
    {{ $label }}
    <input
        class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-[var(--brand-primary)]"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ $value }}" @endif
        @required($required)
    >
</label>
