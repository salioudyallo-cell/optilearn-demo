<x-layouts.public :title="$lesson->title.' — '.config('brand.name')">
    @php($completedIds = auth()->check() ? app(\App\Services\LearningNavigator::class)->completedLessonIds(auth()->user(), $course) : collect())

    <div class="mx-auto max-w-6xl px-4 sm:px-6 py-8 grid lg:grid-cols-[300px_1fr] gap-8">

        {{-- Sommaire --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <a href="{{ route('learn.course', $course) }}" class="inline-flex items-center gap-1.5 text-sm text-ink-500 hover:text-brand-blue-700 mb-3 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                {{ __('Sommaire') }}
            </a>
            <div class="bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft overflow-hidden">
                @foreach ($course->modules->sortBy('position') as $module)
                    <div class="border-b border-ink-100 last:border-b-0">
                        <p class="px-4 pt-3.5 pb-2 text-xs font-semibold uppercase tracking-wider text-ink-400">{{ __('Module :n', ['n' => $module->position]) }} · {{ $module->title }}</p>
                        <ul class="pb-2">
                            @foreach ($module->lessons->sortBy('position') as $item)
                                <li>
                                    <a href="{{ route('learn.lesson', $item) }}"
                                       @if($item->id === $lesson->id) aria-current="true" @endif
                                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm transition {{ $item->id === $lesson->id ? 'bg-brand-blue-50 text-brand-blue-800 font-semibold border-l-2 border-brand-blue-600' : 'text-ink-600 hover:bg-ink-50 border-l-2 border-transparent' }}">
                                        @if ($completedIds->contains($item->id))
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-100 shrink-0">
                                                <svg class="w-3.5 h-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                            </span>
                                        @else
                                            <span class="w-5 h-5 shrink-0 flex items-center justify-center"><x-public.lesson-icon :type="$item->type->value" /></span>
                                        @endif
                                        <span class="truncate">{{ $item->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </aside>

        {{-- Contenu --}}
        <main>
            <p class="text-sm font-semibold text-brand-orange-600">{{ $lesson->module->title }}</p>
            <h1 class="mt-1.5 text-2xl sm:text-3xl font-bold text-brand-charcoal">{{ $lesson->title }}</h1>

            <div class="mt-6">
                @auth
                    @if ($lesson->type->value === 'quiz')
                        <livewire:quiz-player :lesson="$lesson" :key="'quiz-'.$lesson->id" />
                    @else
                        <livewire:lesson-player :lesson="$lesson" :key="'player-'.$lesson->id" />
                    @endif
                @else
                    {{-- Apercu public. Une lecon d'essai est REELLEMENT lisible sans
                         compte : c'est le levier de conversion de la fiche formation.
                         Le contenu paye, lui, reste ferme (LessonPolicy). --}}
                    @if ($lesson->is_preview)
                        @if ($lesson->type->value === 'text')
                            <article class="prose-lesson max-w-none">{!! \Illuminate\Support\Str::markdown($lesson->content ?? '') !!}</article>

                            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                                <x-ui.button :href="route('register')" variant="primary">{{ __('Demander l’accès') }}</x-ui.button>
                                <x-ui.button :href="route('catalog.show', $course)" variant="secondary">{{ __('Voir le programme complet') }}</x-ui.button>
                            </div>
                        @else
                            <x-learn.preview-player :lesson="$lesson" />
                        @endif
                    @else
                        <div class="aspect-video rounded-2xl bg-brand-gradient flex flex-col items-center justify-center text-white text-center px-6">
                            <svg class="w-10 h-10 mb-3 opacity-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            <p class="max-w-sm text-white/85">{{ __('Cette leçon fait partie du contenu réservé. Activez votre accès pour la consulter.') }}</p>
                        </div>

                        <div class="mt-6 flex flex-col sm:flex-row gap-3">
                            <x-ui.button :href="route('login')" variant="primary">{{ __('Se connecter') }}</x-ui.button>
                            <x-ui.button :href="route('catalog.show', $course)" variant="secondary">{{ __('Voir la formation') }}</x-ui.button>
                        </div>
                    @endif
                @endauth
            </div>

            {{-- Navigation précédent / suivant --}}
            <div class="mt-10 flex items-center justify-between gap-4 border-t border-ink-100 pt-6">
                @if ($previous)
                    <a href="{{ route('learn.lesson', $previous) }}" class="group inline-flex items-center gap-2 text-sm font-medium text-ink-600 hover:text-brand-blue-700 transition">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                        <span class="truncate max-w-[36vw]">{{ $previous->title }}</span>
                    </a>
                @else
                    <span></span>
                @endif

                @if ($next)
                    <a href="{{ route('learn.lesson', $next) }}" class="group inline-flex items-center gap-2 text-sm font-semibold text-brand-blue-700 hover:text-brand-blue-800 transition">
                        <span class="truncate max-w-[36vw]">{{ $next->title }}</span>
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                @else
                    <x-ui.button :href="route('learn.course', $course)" variant="primary" size="sm">{{ __('Terminer — retour au sommaire') }}</x-ui.button>
                @endif
            </div>
        </main>
    </div>
</x-layouts.public>
