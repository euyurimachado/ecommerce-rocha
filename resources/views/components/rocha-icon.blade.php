@props(['name', 'class' => 'size-5'])

@php
    $isFilled = str_contains($class, 'fill-current');

    $icons = [
        'arrow-left' => 'fa-solid fa-arrow-left',
        'badge-check' => 'fa-solid fa-circle-check',
        'chevron-right' => 'fa-solid fa-chevron-right',
        'credit-card' => 'fa-regular fa-credit-card',
        'cookie' => 'fa-solid fa-cookie-bite',
        'bolt' => 'fa-solid fa-bolt',
        'bottle-water' => 'fa-solid fa-bottle-water',
        'boxes-stacked' => 'fa-solid fa-boxes-stacked',
        'capsules' => 'fa-solid fa-capsules',
        'dumbbell' => 'fa-solid fa-dumbbell',
        'flask-vial' => 'fa-solid fa-flask-vial',
        'heart' => $isFilled ? 'fa-solid fa-heart' : 'fa-regular fa-heart',
        'home' => 'fa-solid fa-house',
        'map-pin' => 'fa-solid fa-location-dot',
        'message-circle' => 'fa-brands fa-whatsapp',
        'package' => 'fa-solid fa-box',
        'package-open' => 'fa-solid fa-box-open',
        'plus' => 'fa-solid fa-plus',
        'search' => 'fa-solid fa-magnifying-glass',
        'share-2' => 'fa-solid fa-share-nodes',
        'shield-check' => 'fa-solid fa-shield-halved',
        'shopping-cart' => 'fa-solid fa-cart-shopping',
        'sparkles' => 'fa-solid fa-wand-magic-sparkles',
        'store' => 'fa-solid fa-store',
        'star' => 'fa-solid fa-star',
        'tag' => 'fa-solid fa-tag',
        'truck' => 'fa-solid fa-truck-fast',
        'user' => 'fa-regular fa-user',
        'wifi-off' => 'fa-solid fa-wifi',
    ];

    $icon = $icons[$name] ?? 'fa-regular fa-circle';
@endphp

<i {{ $attributes->merge(['class' => 'rocha-fa-icon fa-fw '.$icon.' '.$class, 'aria-hidden' => 'true']) }}></i>
