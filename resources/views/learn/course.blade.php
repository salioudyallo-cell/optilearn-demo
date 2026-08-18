<x-layouts.public :title="$course->title.' — '.config('brand.name')">
    @php($completedIds = app(\App\Services\LearningNavigator::class)->completedLessonIds(auth()->user(), $course))

    <section class="bg-hero-mesh border-b border-ink-100">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 py-12">
            <nav class="flex items-center gap-1.5 text-sm text-ink-400 mb-5">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-blue-700 transition">{{ __('Mon espace') }}</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="text-ink-600 truncate">{{ $course->title }}</span>
            </nav>

            <h1 class="text-3xl sm:text-4xl font-bold text-brand-charcoal leading-tight">{{ $course->title }}</h1>

            <div class="mt-6 max-w-md">
                <div class="flex justify-between text-sm text-ink-500 mb-1.5">
                    <span>{{ __(':done sur :total leçons terminées', ['done' => $progress['completed'], 'total' => $progress['total']]) }}</span>
                    <span class="font-semibold text-brand-charcoal">{{ $progress['percent'] }} %</span>
                </div>
                <div class="h-2.5 rounded-full bg-ink-100 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-orange-400 to-brand-orange-600 transition-all duration-500" style="width: {{ $progress['percent'] }}%"></div>
                </div>
            </div>

            @if ($resumeLesson)
                <x-ui.button :href="route('learn.lesson', $resumeLesson)" variant="primary" size="lg" class="mt-6">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
                    {{ $progress['completed'] > 0 ? __('Reprendre la formation') : __('Commencer la formation') }}
                </x-ui.button>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 sm:px-6 py-12">
        <h2 class="text-xl font-bold text-brand-charcoal mb-6">{{ __('Programme') }}</h2>

        <div class="space-y-3">
            @foreach ($course->modules->sortBy('position') as $module)
                <div x-data="{ open: true }" class="bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft overflow-hidden">
                    <button type="button" x-on:click="open = ! open" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-ink-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-blue-500 transition" :aria-expanded="open.toString()">
                        <span class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-blue-50 text-brand-blue-700 text-sm font-bold">{{ $module->position }}</span>
                            <span class="font-semibold text-brand-charcoal">{{ $module->title }}</span>
                        </span>
                        <svg class="w-5 h-5 text-ink-400 shrink-0 transition-transform duration-300" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>

                    <ul x-show="open" x-collapse class="divide-y divide-ink-100 border-t border-ink-100">
                        @foreach ($module->lessons->sortBy('position') as $lesson)
                            <li>
                                <a href="{{ route('learn.lesson', $lesson) }}" class="group flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-brand-blue-50/50 transition">
                                    <span class="flex items-center gap-3 min-w-0">
                                        @if ($completedIds->contains($lesson->id))
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100 shrink-0">
                                                <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                            </span>
                                        @else
                                            <x-public.lesson-icon :type="$lesson->type->value" />
                                        @endif
                                        <span class="text-sm text-ink-600 group-hover:text-brand-charcoal truncate transition">{{ $lesson->title }}</span>
                                    </span>
                                    <svg class="w-4 h-4 text-ink-300 group-hover:text-brand-blue-600 shrink-0 transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
