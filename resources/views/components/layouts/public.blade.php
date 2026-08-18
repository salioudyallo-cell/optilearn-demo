@props([
    'title' => null,
    'metaDescription' => null,
    'ogImage' => null,
    'noindex' => false,
])

@php
    $resolvedTitle = $title ?? config('app.name');
    $description = $metaDescription ?? config('brand.tagline');
    // Image OG facultative : rendue seulement si fournie, pour ne jamais pointer un fichier absent.
    $image = $ogImage;
    $favicon = config('brand.favicon');
    $faviconType = \Illuminate\Support\Str::endsWith($favicon, '.svg') ? 'image/svg+xml' : 'image/png';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ config('brand.colors.primary') }}">
    <link rel="icon" type="{{ $faviconType }}" href="{{ asset($favicon) }}">

    <title>{{ $resolvedTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    {{-- Tant que le site n'est pas declare indexable, on interdit l'indexation
         (moteurs + robots IA) sur toutes les pages, quelle que soit la prop noindex. --}}
    @if ($noindex || ! config('lms.site.indexable'))
        <meta name="robots" content="noindex, nofollow">
        <meta name="googlebot" content="noindex, nofollow">
    @endif

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('brand.name') }}">
    <meta property="og:title" content="{{ $resolvedTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $resolvedTitle }}">
    <meta name="twitter:description" content="{{ $description }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-brand-theme />
    {{-- Alpine est fourni par Livewire ; ces directives le chargent sur toutes les pages
         publiques, y compris celles sans composant Livewire (evite le double Alpine). --}}
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen flex flex-col bg-canvas-soft text-ink-600 antialiased">
    <x-demo-banner />
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-card focus:ring-2 focus:ring-brand-blue-500">
        {{ __('Aller au contenu') }}
    </a>

    <x-public.header />

    <main id="contenu" class="flex-1">
        {{ $slot }}
    </main>

    <x-public.footer />

    @livewireScripts
    @stack('scripts')
</body>
</html>
