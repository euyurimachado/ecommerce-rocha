@props(['name', 'class' => 'size-5'])

<svg {{ $attributes->merge(['class' => $class, 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('badge-check')
            <path d="m9 12 2 2 4-4" />
            <path d="M7.4 7.4h.01" />
            <path d="M18 13.5V7.9a2 2 0 0 0-.6-1.4l-2.9-2.9A2 2 0 0 0 13.1 3H7a2 2 0 0 0-2 2v6.1a2 2 0 0 0 .6 1.4l5.9 5.9a2 2 0 0 0 2.8 0l2.1-2.1" />
            @break

        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break

        @case('heart')
            <path d="M19.5 12.6 12 20l-7.5-7.4A5 5 0 0 1 12 6a5 5 0 0 1 7.5 6.6" />
            @break

        @case('home')
            <path d="m3 11 9-8 9 8" />
            <path d="M5 10v10h14V10" />
            <path d="M9 20v-6h6v6" />
            @break

        @case('map-pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0" />
            <circle cx="12" cy="10" r="3" />
            @break

        @case('package')
            <path d="m7.5 4.3 9 5.2" />
            <path d="m21 8-9 5-9-5" />
            <path d="M3 8v8l9 5 9-5V8" />
            <path d="M12 13v8" />
            <path d="m3.3 7.6 8.2-4.7a1 1 0 0 1 1 0l8.2 4.7" />
            @break

        @case('plus')
            <path d="M5 12h14" />
            <path d="M12 5v14" />
            @break

        @case('search')
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
            @break

        @case('shield-check')
            <path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3z" />
            <path d="m9 12 2 2 4-4" />
            @break

        @case('shopping-cart')
            <circle cx="8" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.1 2.1h2l2.7 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L21 7H5.1" />
            @break

        @case('sparkles')
            <path d="m12 3-1.9 5.8L4 11l6.1 2.2L12 19l1.9-5.8L20 11l-6.1-2.2z" />
            <path d="M5 3v4" />
            <path d="M3 5h4" />
            <path d="M19 17v4" />
            <path d="M17 19h4" />
            @break

        @case('star')
            <path d="m12 2 3.1 6.3 6.9 1-5 4.8 1.2 6.9-6.2-3.3L5.8 21 7 14.1 2 9.3l6.9-1z" />
            @break

        @case('tag')
            <path d="M12.6 2.6H4a2 2 0 0 0-2 2v8.6a2 2 0 0 0 .6 1.4l6.8 6.8a2 2 0 0 0 2.8 0l9.2-9.2a2 2 0 0 0 0-2.8l-6.8-6.8a2 2 0 0 0-1.4-.6" />
            <circle cx="7.5" cy="7.5" r=".5" />
            @break

        @case('truck')
            <path d="M14 18V6a2 2 0 0 0-2-2H3v14h2" />
            <path d="M15 18H9" />
            <path d="M19 18h2v-6.5L18 8h-4" />
            <circle cx="7" cy="18" r="2" />
            <circle cx="17" cy="18" r="2" />
            @break

        @case('user')
            <path d="M19 21a7 7 0 0 0-14 0" />
            <circle cx="12" cy="7" r="4" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
