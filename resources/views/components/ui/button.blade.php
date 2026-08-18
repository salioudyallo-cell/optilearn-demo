@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    $base = 'group relative inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-60 disabled:pointer-events-none select-none';

    $variants = [
        // CTA principal — un seul par ecran (Von Restorff).
        'primary' => 'bg-brand-orange-500 text-white hover:bg-brand-orange-600 focus-visible:ring-brand-orange-500 shadow-[0_6px_18px_-4px_rgba(249,118,0,0.45)] hover:shadow-[0_10px_26px_-6px_rgba(249,118,0,0.55)] hover:-translate-y-0.5 active:translate-y-0',
        // Action de confiance.
        'blue' => 'bg-brand-blue-600 text-white hover:bg-brand-blue-700 focus-visible:ring-brand-blue-600 shadow-soft hover:-translate-y-0.5 active:translate-y-0',
        // Secondaire — contour discret.
        'secondary' => 'bg-white text-ink-800 ring-1 ring-ink-200 hover:ring-ink-300 hover:bg-ink-50 focus-visible:ring-brand-blue-500 shadow-soft',
        // Sur fond sombre.
        'inverse' => 'bg-white text-brand-blue-900 hover:bg-ink-100 focus-visible:ring-white',
        // Fantome.
        'ghost' => 'text-ink-700 hover:text-brand-blue-700 hover:bg-brand-blue-50 focus-visible:ring-brand-blue-500',
    ];

    $sizes = [
        'sm' => 'text-sm px-3.5 py-2',
        'md' => 'text-sm px-5 py-2.5',
        'lg' => 'text-base px-6 py-3.5',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
