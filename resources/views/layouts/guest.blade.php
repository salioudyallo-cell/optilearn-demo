<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="{{ config('brand.colors.primary') }}">
        @php $favicon = config('brand.favicon'); @endphp
        <link rel="icon" type="{{ \Illuminate\Support\Str::endsWith($favicon, '.svg') ? 'image/svg+xml' : 'image/png' }}" href="{{ asset($favicon) }}">

        <title>{{ $title ?? config('app.name') }}</title>

        {{-- Police Sora auto-hebergee via app.css : aucun CDN. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-brand-theme />
    </head>
    <body class="font-sans text-ink-600 antialiased bg-hero-mesh">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10">
            <a href="{{ route('home') }}" class="flex items-center mb-8 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue-500">
                <x-brand-mark />
            </a>

            <div class="w-full sm:max-w-md">
                <div class="px-6 py-8 sm:px-8 bg-white/90 backdrop-blur shadow-card ring-1 ring-ink-100 rounded-3xl">
                    {{ $slot }}
                </div>
                <p class="mt-6 text-center text-sm text-ink-400">
                    &copy; {{ date('Y') }} {{ config('brand.short_name') }}. {{ __('Tous droits réservés.') }}
                </p>
            </div>
        </div>
    </body>
</html>
