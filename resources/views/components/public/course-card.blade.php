@props(['course'])

@use('App\Support\Money')

<article class="group card-lift relative flex flex-col bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft overflow-hidden">
    <a href="{{ route('catalog.show', $course) }}" class="block focus-visible:outline-none" tabindex="-1" aria-hidden="true">
        <div class="relative aspect-[16/10] overflow-hidden">
            @if ($course->cover_path)
                <img src="{{ Storage::disk(config('lms.courses.media_disk'))->url($course->cover_path) }}"
                     alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" loading="lazy">
            @else
                <div class="h-full w-full bg-hero-mesh flex items-center justify-center">
                    <x-application-logo class="h-12 w-12 opacity-90" />
                </div>
            @endif
            <span class="absolute top-3 left-3 inline-flex items-center rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-xs font-semibold text-ink-700 ring-1 ring-ink-200">
                {{ $course->level->label() }}
            </span>
        </div>
    </a>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs text-ink-400 mb-2">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                @if ($course->duration_minutes > 0){{ intdiv($course->duration_minutes, 60) }} h{{ $course->duration_minutes % 60 ? ' '.($course->duration_minutes % 60).' min' : '' }}@else{{ __('À votre rythme') }}@endif
            </span>
            @isset($course->lessons_count)
                <span aria-hidden="true">·</span>
                <span>{{ $course->lessons_count }} {{ __('leçons') }}</span>
            @endisset
        </div>

        <h3 class="text-lg font-semibold text-brand-charcoal leading-snug">
            <a href="{{ route('catalog.show', $course) }}" class="hover:text-brand-blue-700 transition focus-visible:outline-none">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $course->title }}
            </a>
        </h3>

        @if ($course->subtitle)
            <p class="mt-2 text-sm text-ink-500 line-clamp-2">{{ $course->subtitle }}</p>
        @endif

        @feature('reviews')
            @if (($course->reviews_count ?? 0) > 0)
                <div class="mt-2 flex items-center gap-1.5">
                    <span class="text-brand-yellow" aria-hidden="true">★</span>
                    <span class="text-sm font-semibold text-brand-charcoal">{{ round($course->reviews_avg_rating, 1) }}</span>
                    <span class="text-xs text-ink-400">({{ $course->reviews_count }})</span>
                </div>
            @endif
        @endfeature

        <div class="mt-5 pt-4 border-t border-ink-100 flex items-center justify-between">
            {{-- Prix affichés uniquement quand la capacité « tarification » est active
                 (mode commercial). En mode entreprise, aucun tarif n'est exposé. --}}
            @feature('pricing')
                <div class="flex flex-col">
                    <span class="text-xs text-ink-400">{{ $course->price_fcfa > 0 ? __('à partir de') : '' }}</span>
                    <span class="text-base font-bold text-brand-charcoal">
                        {{ $course->price_fcfa > 0 ? Money::fcfa($course->price_fcfa) : __('Gratuit') }}
                    </span>
                </div>
            @else
                <span></span>
            @endfeature
            <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand-orange-600 group-hover:gap-2 transition-all">
                {{ __('Découvrir') }}
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </span>
        </div>
    </div>
</article>
