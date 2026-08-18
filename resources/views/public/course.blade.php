<x-layouts.public :title="$course->title.' — '.config('brand.name')"
    :metaDescription="$course->subtitle">

    @php
        $whatsapp = config('lms.contact.whatsapp_number');
        $waMessage = rawurlencode(__('Bonjour, je souhaite accéder à la formation « :course ».', ['course' => $course->title]));
        $waLink = $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.$waMessage : null;
        $lessonCount = $course->modules->sum(fn ($m) => $m->lessons->count());

        // schema.org Course (JSON-LD) pour l'indexation enrichie.
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course->title,
            'description' => $course->subtitle ?? \Illuminate\Support\Str::limit(strip_tags($course->description ?? ''), 200),
            'url' => route('catalog.show', $course),
            'inLanguage' => 'fr',
            'provider' => [
                '@type' => 'Organization',
                'name' => config('brand.short_name'),
                'url' => url('/'),
            ],
        ];
    @endphp

    @push('head')
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    <section class="bg-hero-mesh border-b border-ink-100">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-8 pb-12">
            <nav class="flex items-center gap-1.5 text-sm text-ink-400 mb-6" aria-label="Fil d’Ariane">
                <a href="{{ route('catalog.index') }}" class="hover:text-brand-blue-700 transition">{{ __('Formations') }}</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="text-ink-600 truncate">{{ $course->title }}</span>
            </nav>

            <div class="grid lg:grid-cols-3 gap-10">
                <div class="lg:col-span-2">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="inline-flex items-center rounded-full bg-brand-blue-50 px-3 py-1 text-xs font-semibold text-brand-blue-700 ring-1 ring-brand-blue-100">
                            {{ $course->level->label() }}
                        </span>
                        @if ($course->duration_minutes > 0)
                            <span class="inline-flex items-center gap-1.5 text-xs text-ink-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                {{ intdiv($course->duration_minutes, 60) }} h {{ $course->duration_minutes % 60 ? ($course->duration_minutes % 60).' min' : '' }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-brand-charcoal leading-[1.1]">{{ $course->title }}</h1>
                    @if ($course->subtitle)
                        <p class="mt-5 text-lg text-ink-500 leading-relaxed">{{ $course->subtitle }}</p>
                    @endif
                    <div class="mt-6 flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue-600 text-white text-sm font-semibold">
                            {{ \Illuminate\Support\Str::of($course->instructor->name)->explode(' ')->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->implode('') }}
                        </span>
                        <div class="text-sm">
                            <p class="text-ink-400">{{ __('Formation animée par') }}</p>
                            <p class="font-semibold text-brand-charcoal">{{ $course->instructor->name }}</p>
                        </div>
                    </div>
                </div>

                {{-- Carte d'action (ancrage prix + CTA unique) --}}
                <aside class="lg:col-span-1">
                    <div class="bg-white rounded-2xl ring-1 ring-ink-100 shadow-card p-6 lg:sticky lg:top-24">
                        {{-- Prix et discours d'achat : mode commercial uniquement. --}}
                        @feature('pricing')
                            <div class="flex items-baseline gap-2">
                                <p class="text-3xl font-bold text-brand-charcoal">
                                    {{ $course->price_fcfa > 0 ? \App\Support\Money::fcfa($course->price_fcfa) : __('Gratuit') }}
                                </p>
                            </div>
                            <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-green-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Accès à vie, sans abonnement') }}
                            </p>
                        @else
                            <p class="text-lg font-semibold text-brand-charcoal">{{ __('Formation interne') }}</p>
                            <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-brand-blue-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Accès attribué par votre organisation') }}
                            </p>
                        @endfeature

                        @feature('cart')
                            {{-- Mode commercial : achat direct. --}}
                            @auth
                                @if (auth()->user()->hasAccessToCourse($course))
                                    <x-ui.button :href="route('learn.course', $course)" variant="primary" class="w-full mt-5">{{ __('Accéder à la formation') }}</x-ui.button>
                                @else
                                    <form method="POST" action="{{ route('checkout.store', $course) }}" class="mt-5 space-y-3">
                                        @csrf
                                        <div>
                                            <input type="text" name="coupon" value="{{ old('coupon') }}" placeholder="{{ __('Code promo (facultatif)') }}"
                                                   class="w-full rounded-lg border-ink-200 text-sm focus:border-brand-blue-500 focus:ring-brand-blue-500">
                                            @error('coupon')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                        <x-ui.button type="submit" variant="primary" class="w-full">
                                            {{ $course->price_fcfa > 0 ? __('Acheter maintenant') : __('Obtenir gratuitement') }}
                                        </x-ui.button>
                                    </form>
                                @endif
                            @else
                                <x-ui.button :href="route('login')" variant="primary" class="w-full mt-5">{{ __('Se connecter pour acheter') }}</x-ui.button>
                            @endauth
                            <p class="mt-3 text-xs text-ink-400 leading-relaxed">
                                {{ __('Paiement par Wave, Orange Money, virement ou espèces. Accès activé dès confirmation.') }}
                            </p>
                        @else
                            {{-- Mode entreprise ou hors capacité panier. --}}
                            <x-ui.button :href="$waLink ?? route('register')" variant="primary" class="w-full mt-5"
                                :target="$waLink ? '_blank' : null" :rel="$waLink ? 'noopener' : null">
                                @feature('pricing')
                                    {{ __('Demander l’accès') }}
                                @else
                                    {{ __('Se connecter') }}
                                @endfeature
                            </x-ui.button>
                        @endfeature

                        <dl class="mt-6 space-y-3 text-sm border-t border-ink-100 pt-5">
                            <div class="flex items-center justify-between">
                                <dt class="inline-flex items-center gap-2 text-ink-500"><svg class="w-4 h-4 text-brand-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>{{ __('Modules') }}</dt>
                                <dd class="font-semibold text-brand-charcoal">{{ $course->modules->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="inline-flex items-center gap-2 text-ink-500"><svg class="w-4 h-4 text-brand-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>{{ __('Leçons') }}</dt>
                                <dd class="font-semibold text-brand-charcoal">{{ $lessonCount }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="inline-flex items-center gap-2 text-ink-500"><svg class="w-4 h-4 text-brand-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" /></svg>{{ __('Certificat') }}</dt>
                                <dd class="font-semibold text-brand-charcoal">{{ __('Inclus') }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 grid lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 py-14">
            {{-- Description --}}
            @if ($course->description)
                <section class="mb-14">
                    <h2 class="text-2xl font-bold text-brand-charcoal mb-4">{{ __('À propos de cette formation') }}</h2>
                    <div class="prose-lesson">{!! \Illuminate\Support\Str::markdown($course->description) !!}</div>
                </section>
            @endif

            {{-- Programme --}}
            <section>
                <h2 class="text-2xl font-bold text-brand-charcoal mb-2">{{ __('Programme') }}</h2>
                <p class="text-ink-500 mb-6">{{ __(':modules modules · :lessons leçons', ['modules' => $course->modules->count(), 'lessons' => $lessonCount]) }}</p>

                <div class="space-y-3" x-data>
                    @foreach ($course->modules as $module)
                        {{-- Accordeon en Alpine : purement visuel, aucune donnee serveur. --}}
                        <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft overflow-hidden">
                            <button type="button" x-on:click="open = ! open"
                                    class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-ink-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-blue-500 transition"
                                    :aria-expanded="open.toString()">
                                <span class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-blue-50 text-brand-blue-700 text-sm font-bold">{{ $module->position }}</span>
                                    <span class="font-semibold text-brand-charcoal">{{ $module->title }}</span>
                                </span>
                                <svg class="w-5 h-5 text-ink-400 shrink-0 transition-transform duration-300" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <ul x-show="open" x-collapse class="divide-y divide-ink-100 border-t border-ink-100">
                                @foreach ($module->lessons as $lesson)
                                    <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <x-public.lesson-icon :type="$lesson->type->value" />
                                            <span class="text-sm text-ink-600 truncate">{{ $lesson->title }}</span>
                                        </div>

                                        @if ($lesson->is_preview)
                                            <a href="{{ route('learn.lesson', $lesson) }}"
                                               class="shrink-0 inline-flex items-center gap-1 rounded-full bg-brand-orange-50 px-2.5 py-1 text-xs font-semibold text-brand-orange-700 hover:bg-brand-orange-100 transition">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
                                                {{ __('Aperçu gratuit') }}
                                            </a>
                                        @else
                                            <span class="shrink-0 text-ink-300" aria-label="{{ __('Contenu verrouillé') }}" title="{{ __('Contenu verrouillé') }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                </svg>
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Avis : mode Commercial uniquement. --}}
            @feature('reviews')
                @if (session('status'))
                    <div class="mt-8 rounded-xl bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('status') }}</div>
                @endif
                <x-public.reviews :course="$course" />
            @endfeature
        </div>
    </div>

</x-layouts.public>
