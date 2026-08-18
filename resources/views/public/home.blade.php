<x-layouts.public :title="config('brand.name').' — '.__('Se former aux métiers du digital en Afrique de l’Ouest')"
    :metaDescription="__('Formations en marketing digital, SEO, publicité en ligne, IA et génération de leads. Pour les équipes et les indépendants au Sénégal, en Côte d’Ivoire et au Mali.')">

    {{-- ═══════════ HERO ═══════════ --}}
    <section class="relative overflow-hidden bg-hero-mesh">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-14 sm:pt-20 pb-20 lg:pb-28 grid lg:grid-cols-2 gap-14 items-center">
            <div class="animate-rise">
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-medium text-brand-blue-700 ring-1 ring-brand-blue-100 shadow-soft">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-orange-500"></span>
                    </span>
                    {{ config('brand.region') }}
                </span>

                <h1 class="mt-6 text-4xl sm:text-5xl lg:text-[3.4rem] font-bold text-brand-charcoal leading-[1.08]">
                    {{ __('Maîtrisez les compétences digitales qui') }}
                    <span class="text-gradient-brand">{{ __('font décoller votre activité.') }}</span>
                </h1>

                <p class="mt-6 text-lg text-ink-500 leading-relaxed max-w-xl">
                    {{ __('SEO, publicité Meta et Google, IA, automatisation, génération de leads. Des formations concrètes, animées par des praticiens, pensées pour le contexte ouest-africain.') }}
                </p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <x-ui.button :href="route('catalog.index')" variant="primary" size="lg">
                        {{ __('Explorer les formations') }}
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </x-ui.button>
                    <x-ui.button :href="route('register')" variant="secondary" size="lg">
                        {{ __('Créer un compte gratuit') }}
                    </x-ui.button>
                </div>

                <div class="mt-8 flex items-center gap-3 text-sm text-ink-500">
                    <svg class="w-5 h-5 text-brand-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    {{ __('Accès à vie · Certificat vérifiable · Sans engagement') }}
                </div>
            </div>

            {{-- Visuel : carte produit flottante (progressive disclosure d'une leçon). --}}
            <div class="relative animate-rise" style="animation-delay: .1s">
                <div class="absolute -inset-4 bg-brand-blue-500/10 blur-3xl rounded-full" aria-hidden="true"></div>
                <div class="relative rounded-3xl bg-white ring-1 ring-ink-100 shadow-lift p-5 rotate-1 hover:rotate-0 transition-transform duration-500">
                    <div class="aspect-video rounded-2xl bg-brand-gradient flex items-center justify-center">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-white/95 shadow-lg">
                            <svg class="w-6 h-6 text-brand-orange-600 ml-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-brand-charcoal">{{ __('Module 1 · SEO B2B') }}</p>
                            <p class="text-xs text-ink-400">{{ __('Leçon 2 sur 6') }}</p>
                        </div>
                        <span class="text-xs font-semibold text-brand-blue-700 bg-brand-blue-50 rounded-full px-2.5 py-1">33 %</span>
                    </div>
                    <div class="mt-3 h-1.5 rounded-full bg-ink-100 overflow-hidden">
                        <div class="h-full w-1/3 rounded-full bg-brand-orange-500"></div>
                    </div>
                </div>
                <div class="absolute -bottom-5 -left-5 hidden sm:flex items-center gap-2 rounded-2xl bg-white ring-1 ring-ink-100 shadow-card px-4 py-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100">
                        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold text-brand-charcoal">{{ __('Certificat obtenu') }}</p>
                        <p class="text-[11px] text-ink-400">OPT-2026-1E025D</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ BANDEAU DE CONFIANCE ═══════════ --}}
    <section class="border-y border-ink-100 bg-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-8 grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
            @foreach ([
                ['value' => '9', 'label' => __('domaines couverts')],
                ['value' => '100 %', 'label' => __('en ligne, mobile-first')],
                ['value' => '3', 'label' => __('pays d’Afrique de l’Ouest')],
                ['value' => 'À vie', 'label' => __('accès à vos formations')],
            ] as $stat)
                <div>
                    <p class="text-2xl sm:text-3xl font-bold text-brand-blue-700">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ═══════════ PILIERS DE VALEUR ═══════════ --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6 py-20 sm:py-24">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-brand-orange-600 uppercase tracking-widest">{{ __('Pourquoi') }} {{ config('brand.short_name') }}</p>
            <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-brand-charcoal">{{ __('Une formation faite pour être appliquée dès demain.') }}</h2>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['t' => __('Des compétences applicables'), 'd' => __('Chaque module vise un résultat concret : une campagne qui tourne, un site qui convertit, un flux de leads mesurable.'), 'p' => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z'],
                ['t' => __('Pensé mobile & data-light'), 'd' => __('Vidéos et supports optimisés pour la 3G/4G. Vous suivez vos formations depuis votre téléphone, où que vous soyez.'), 'p' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                ['t' => __('Un accompagnement humain'), 'd' => __('L’accès est accordé par :brand et un interlocuteur vous suit. Vous avancez à votre rythme, jamais seul.', ['brand' => config('brand.short_name')]), 'p' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
            ] as $pillar)
                <div class="card-lift bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft p-7">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-blue-50 text-brand-blue-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $pillar['p'] }}" /></svg>
                    </span>
                    <h3 class="mt-5 text-lg font-semibold text-brand-charcoal">{{ $pillar['t'] }}</h3>
                    <p class="mt-2 text-sm text-ink-500 leading-relaxed">{{ $pillar['d'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ═══════════ COMMENT ÇA MARCHE ═══════════ --}}
    <section class="bg-white border-y border-ink-100">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-20 sm:py-24">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-brand-orange-600 uppercase tracking-widest">{{ __('Comment ça marche') }}</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-brand-charcoal">{{ __('De l’inscription au certificat, en trois étapes.') }}</h2>
            </div>

            <ol class="mt-12 grid gap-8 md:grid-cols-3 relative">
                @foreach ([
                    ['n' => '01', 't' => __('Créez votre compte'), 'd' => __('Explorez le catalogue librement et repérez la formation qui répond à votre objectif.')],
                    ['n' => '02', 't' => __('Activez votre accès'), 'd' => __('Réglez avec :brand (Wave, Orange Money, virement…) et recevez votre code d’activation.', ['brand' => config('brand.short_name')])],
                    ['n' => '03', 't' => __('Apprenez et certifiez-vous'), 'd' => __('Suivez les modules à votre rythme, réussissez le quiz et obtenez un certificat vérifiable.')],
                ] as $step)
                    <li class="relative">
                        <span class="text-5xl font-bold text-ink-200">{{ $step['n'] }}</span>
                        <h3 class="mt-2 text-lg font-semibold text-brand-charcoal">{{ $step['t'] }}</h3>
                        <p class="mt-2 text-sm text-ink-500 leading-relaxed">{{ $step['d'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ═══════════ FORMATIONS EN AVANT ═══════════ --}}
    @if ($featuredCourses->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 sm:px-6 py-20 sm:py-24">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-12">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-orange-600 uppercase tracking-widest">{{ __('Catalogue') }}</p>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-brand-charcoal">{{ __('Nos formations phares') }}</h2>
                </div>
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-blue-700 hover:gap-2.5 transition-all">
                    {{ __('Tout le catalogue') }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredCourses as $course)
                    <x-public.course-card :course="$course" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════════ CTA FINAL ═══════════ --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6 pb-20">
        <div class="relative overflow-hidden rounded-3xl bg-brand-gradient px-6 py-16 sm:px-16 sm:py-20 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white max-w-2xl mx-auto leading-tight">
                {{ __('Prêt à transformer vos compétences en résultats ?') }}
            </h2>
            <p class="mt-4 text-white/75 max-w-xl mx-auto">
                {{ __('Créez votre compte gratuitement, explorez le catalogue, puis demandez votre accès à la formation qui vous intéresse.') }}
            </p>
            <div class="mt-9 flex flex-col sm:flex-row gap-3 justify-center">
                <x-ui.button :href="route('register')" variant="primary" size="lg">{{ __('Commencer maintenant') }}</x-ui.button>
                <x-ui.button :href="route('catalog.index')" variant="inverse" size="lg">{{ __('Voir les formations') }}</x-ui.button>
            </div>
        </div>
    </section>

</x-layouts.public>
