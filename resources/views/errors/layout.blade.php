<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('brand.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    @php $favicon = config('brand.favicon'); @endphp
    <link rel="icon" type="{{ \Illuminate\Support\Str::endsWith($favicon, '.svg') ? 'image/svg+xml' : 'image/png' }}" href="{{ asset($favicon) }}">
    @vite(['resources/css/app.css'])
    <x-brand-theme />
</head>
<body class="min-h-screen bg-hero-mesh text-ink-600 antialiased">
    <main class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
        <a href="{{ url('/') }}" class="flex items-center gap-2 mb-10">
            <x-brand-mark />
        </a>

        <p class="text-7xl sm:text-8xl font-bold text-gradient-brand leading-none">@yield('code')</p>
        <h1 class="mt-6 text-2xl sm:text-3xl font-bold text-brand-charcoal">@yield('title')</h1>
        <p class="mt-3 max-w-md text-ink-500">@yield('message')</p>

        <div class="mt-9 flex flex-col sm:flex-row gap-3">
            <x-ui.button :href="url('/')" variant="primary" size="lg">{{ __('Retour à l’accueil') }}</x-ui.button>
            @hasSection('secondary')
                @yield('secondary')
            @endif
        </div>
    </main>
</body>
</html>
