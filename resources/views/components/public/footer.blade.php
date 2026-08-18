<footer class="bg-brand-gradient text-white/70 mt-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 py-16">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-5 space-y-4">
                {{-- Variante claire sur le dégradé foncé : image blanche si fournie,
                     sinon emblème + nom en blanc. --}}
                <a href="{{ route('home') }}" class="inline-flex items-center rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60" aria-label="{{ config('brand.name') }}, accueil">
                    @php $logoWhite = config('brand.logo_image_white'); @endphp
                    @if ($logoWhite)
                        <img src="{{ asset($logoWhite) }}" alt="{{ config('brand.name') }}" class="h-10 w-auto">
                    @else
                        <x-brand-emblem class="h-9 w-9 shrink-0" />
                        <span class="ml-2.5 text-lg font-bold tracking-tight text-white">{{ config('brand.name') }}</span>
                    @endif
                </a>
                <p class="text-sm leading-relaxed max-w-sm">
                    {{ config('brand.tagline') }}
                </p>
            </div>

            <div class="lg:col-span-2">
                <h2 class="text-xs font-semibold text-white uppercase tracking-widest">{{ __('Formations') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('catalog.index') }}" class="hover:text-white transition">{{ __('Catalogue') }}</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white transition">{{ __('Créer un compte') }}</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white transition">{{ __('Se connecter') }}</a></li>
                </ul>
            </div>

            <div class="lg:col-span-2">
                <h2 class="text-xs font-semibold text-white uppercase tracking-widest">{{ __('Informations') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="{{ route('legal.terms') }}" class="hover:text-white transition">{{ __('Conditions générales') }}</a></li>
                    <li><a href="{{ route('legal.notice') }}" class="hover:text-white transition">{{ __('Mentions légales') }}</a></li>
                    <li><a href="{{ route('legal.privacy') }}" class="hover:text-white transition">{{ __('Confidentialité') }}</a></li>
                </ul>
            </div>

            <div class="lg:col-span-3">
                <h2 class="text-xs font-semibold text-white uppercase tracking-widest">{{ __('Contact') }}</h2>
                <p class="mt-4 text-sm leading-relaxed">
                    {{ config('brand.short_name') }}<br>
                    {{ config('brand.city') }}<br>
                    <a href="mailto:{{ config('brand.email') }}" class="hover:text-white transition">{{ config('brand.email') }}</a>
                </p>
            </div>
        </div>

        <div class="mt-14 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
            <p>&copy; {{ date('Y') }} {{ config('brand.short_name') }}. {{ __('Tous droits réservés.') }}</p>
            <p class="text-white/50">{{ config('brand.slogan') }}</p>
        </div>
    </div>
</footer>
