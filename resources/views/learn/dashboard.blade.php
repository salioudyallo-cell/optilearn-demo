<x-layouts.public :title="__('Mon espace').' — '.config('brand.name')">

    <section class="bg-hero-mesh border-b border-ink-100">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-12 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-brand-orange-600 uppercase tracking-widest">{{ __('Mon espace') }}</p>
                <h1 class="mt-3 text-3xl sm:text-4xl font-bold text-brand-charcoal">{{ __('Bonjour :name 👋', ['name' => \Illuminate\Support\Str::of(auth()->user()->name)->before(' ')]) }}</h1>
                <p class="mt-2 text-ink-500">{{ __('Reprenez là où vous vous êtes arrêté.') }}</p>
            </div>
            <x-ui.button :href="route('activate')" variant="secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                {{ __('Activer une formation') }}
            </x-ui.button>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 py-12">
        @if ($enrollments->isEmpty())
            <div class="bg-white rounded-3xl ring-1 ring-ink-100 shadow-soft p-12 text-center max-w-lg mx-auto">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-blue-50">
                    <svg class="w-7 h-7 text-brand-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                </span>
                <h2 class="mt-5 text-xl font-semibold text-brand-charcoal">{{ __('Votre parcours commence ici') }}</h2>
                <p class="mt-2 text-ink-500">{{ __('Parcourez le catalogue, puis activez votre accès avec le code transmis par :brand.', ['brand' => config('brand.short_name')]) }}</p>
                <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                    <x-ui.button :href="route('catalog.index')" variant="primary">{{ __('Voir le catalogue') }}</x-ui.button>
                    <x-ui.button :href="route('activate')" variant="secondary">{{ __('J’ai un code') }}</x-ui.button>
                </div>
            </div>
        @else
            {{-- Bandeau « Reprendre » : la formation en cours la plus récente. Réduit la
                 friction de reprise — premier levier de complétion. --}}
            @if ($resume)
                <div class="mb-8 rounded-3xl bg-brand-navy text-white p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-6 shadow-lift">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-brand-orange-400 uppercase tracking-widest">{{ __('Reprendre') }}</p>
                        <h2 class="mt-1.5 text-xl sm:text-2xl font-bold truncate">{{ $resume['course']->title }}</h2>
                        <div class="mt-3 flex items-center gap-3">
                            <div class="h-2 flex-1 max-w-xs rounded-full bg-white/15 overflow-hidden">
                                <div class="h-full rounded-full bg-brand-orange-500" style="width: {{ $resume['percent'] }}%"></div>
                            </div>
                            <span class="text-sm text-white/80 shrink-0">{{ $resume['percent'] }} %</span>
                        </div>
                    </div>
                    <x-ui.button :href="route('learn.lesson', $resume['lesson'])" variant="primary" class="shrink-0">
                        {{ __('Continuer la leçon') }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </x-ui.button>
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($enrollments as $enrollment)
                    @php
                        $course = $enrollment->course;
                        $progress = app(\App\Services\LearningNavigator::class)->progressFor(auth()->user(), $course);
                    @endphp
                    <article class="card-lift relative flex flex-col bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft overflow-hidden">
                        <div class="relative aspect-[16/9] overflow-hidden">
                            @if ($course->cover_path)
                                <img src="{{ Storage::disk(config('lms.courses.media_disk'))->url($course->cover_path) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="h-full w-full bg-hero-mesh flex items-center justify-center"><x-application-logo class="h-11 w-11 opacity-90" /></div>
                            @endif
                            @if ($progress['percent'] === 100)
                                <span class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-green-600 text-white px-2.5 py-1 text-xs font-semibold shadow">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    {{ __('Terminé') }}
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="text-lg font-semibold text-brand-charcoal leading-snug">{{ $course->title }}</h2>

                            <div class="mt-4">
                                <div class="flex justify-between text-xs text-ink-500 mb-1.5">
                                    <span>{{ __(':done sur :total leçons', ['done' => $progress['completed'], 'total' => $progress['total']]) }}</span>
                                    <span class="font-semibold text-brand-charcoal">{{ $progress['percent'] }} %</span>
                                </div>
                                <div class="h-2 rounded-full bg-ink-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-orange-400 to-brand-orange-600 transition-all duration-500" style="width: {{ $progress['percent'] }}%"></div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <x-ui.button :href="route('learn.course', $course)" variant="primary" class="w-full">
                                    {{ $progress['completed'] > 0 ? __('Reprendre') : __('Commencer') }}
                                </x-ui.button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
