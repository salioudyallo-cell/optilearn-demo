{{-- En-tête premium : sticky, fond translucide flouté au défilement. Menu mobile en
     Alpine (purement visuel, aucune donnée serveur). --}}
<header x-data="{ open: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 8; window.addEventListener('scroll', () => scrolled = window.scrollY > 8)"
        class="sticky top-0 z-40 transition-colors duration-300"
        :class="scrolled || open ? 'bg-white/85 backdrop-blur-xl border-b border-ink-100' : 'bg-transparent'">
    <nav class="mx-auto max-w-6xl px-4 sm:px-6" aria-label="Navigation principale">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue-500 shrink-0" aria-label="{{ config('brand.name') }}, accueil">
                <x-brand-mark />
            </a>

            <div class="hidden md:flex md:items-center md:gap-1">
                <a href="{{ route('catalog.index') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium text-ink-600 hover:text-brand-blue-700 hover:bg-brand-blue-50 transition">
                    {{ __('Formations') }}
                </a>
                @auth
                    <a href="{{ route('activate') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium text-ink-600 hover:text-brand-blue-700 hover:bg-brand-blue-50 transition">
                        {{ __('Activer un code') }}
                    </a>
                    <a href="{{ route('certificates.index') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium text-ink-600 hover:text-brand-blue-700 hover:bg-brand-blue-50 transition">
                        {{ __('Certificats') }}
                    </a>
                    <div class="ml-2">
                        <x-ui.button :href="route('dashboard')" variant="blue" size="sm">{{ __('Mon espace') }}</x-ui.button>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium text-ink-600 hover:text-brand-blue-700 hover:bg-brand-blue-50 transition">
                        {{ __('Se connecter') }}
                    </a>
                    <div class="ml-2">
                        <x-ui.button :href="route('register')" variant="primary" size="sm">{{ __('Créer un compte') }}</x-ui.button>
                    </div>
                @endauth
            </div>

            <button type="button" x-on:click="open = ! open" class="md:hidden inline-flex items-center justify-center p-2 -mr-2 rounded-lg text-ink-800 hover:bg-ink-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue-500" :aria-expanded="open.toString()" aria-controls="menu-mobile">
                <span class="sr-only">{{ __('Ouvrir le menu') }}</span>
                <svg x-show="! open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             id="menu-mobile" class="md:hidden pb-4 space-y-1">
            <a href="{{ route('catalog.index') }}" class="block px-3 py-2.5 rounded-lg text-base font-medium text-ink-700 hover:bg-brand-blue-50">
                {{ __('Formations') }}
            </a>
            @auth
                <a href="{{ route('activate') }}" class="block px-3 py-2.5 rounded-lg text-base font-medium text-ink-700 hover:bg-brand-blue-50">{{ __('Activer un code') }}</a>
                <a href="{{ route('certificates.index') }}" class="block px-3 py-2.5 rounded-lg text-base font-medium text-ink-700 hover:bg-brand-blue-50">{{ __('Certificats') }}</a>
                <x-ui.button :href="route('dashboard')" variant="blue" class="w-full mt-2">{{ __('Mon espace') }}</x-ui.button>
            @else
                <a href="{{ route('login') }}" class="block px-3 py-2.5 rounded-lg text-base font-medium text-ink-700 hover:bg-brand-blue-50">{{ __('Se connecter') }}</a>
                <x-ui.button :href="route('register')" variant="primary" class="w-full mt-2">{{ __('Créer un compte') }}</x-ui.button>
            @endauth
        </div>
    </nav>
</header>
