@props(['course'])

@php
    $reviews = $course->reviews()->with('user')->latest()->get();
    $count = $reviews->count();
    $average = $count > 0 ? round($reviews->avg('rating'), 1) : null;
    $canReview = auth()->check() && auth()->user()->hasAccessToCourse($course);
    $myReview = $canReview ? $reviews->firstWhere('user_id', auth()->id()) : null;
@endphp

<section class="mt-14" id="avis">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-2xl font-bold text-brand-charcoal">{{ __('Avis des apprenants') }}</h2>
        @if ($average !== null)
            <div class="flex items-center gap-2">
                <div class="flex items-center" aria-label="{{ __(':n sur 5', ['n' => $average]) }}">
                    @for ($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= round($average) ? 'text-brand-yellow' : 'text-ink-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 0 0 .95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 0 0-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 0 0-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 0 0-.36-1.12L2.33 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 0 0 .95-.69L9.05 2.93Z"/></svg>
                    @endfor
                </div>
                <span class="text-sm font-semibold text-brand-charcoal">{{ $average }}</span>
                <span class="text-sm text-ink-400">({{ trans_choice('{0}aucun avis|{1}:count avis|[2,*]:count avis', $count, ['count' => $count]) }})</span>
            </div>
        @endif
    </div>

    {{-- Formulaire : apprenant inscrit uniquement. --}}
    @if ($canReview)
        <form method="POST" action="{{ route('reviews.store', $course) }}" class="mt-6 bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft p-6" x-data="{ rating: {{ $myReview->rating ?? 0 }} }">
            @csrf
            <p class="text-sm font-semibold text-brand-charcoal">{{ $myReview ? __('Modifier votre avis') : __('Donnez votre avis') }}</p>
            <div class="mt-3 flex items-center gap-1">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" @click="rating = {{ $i }}" class="p-0.5" aria-label="{{ __(':n étoile(s)', ['n' => $i]) }}">
                        <svg class="w-7 h-7 transition" :class="rating >= {{ $i }} ? 'text-brand-yellow' : 'text-ink-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 0 0 .95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 0 0-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 0 0-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 0 0-.36-1.12L2.33 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 0 0 .95-.69L9.05 2.93Z"/></svg>
                    </button>
                @endfor
                <input type="hidden" name="rating" :value="rating" required>
            </div>
            <textarea name="comment" rows="3" maxlength="2000" placeholder="{{ __('Votre commentaire (facultatif)') }}"
                      class="mt-3 w-full rounded-xl border-ink-200 focus:border-brand-blue-500 focus:ring-brand-blue-500 text-sm">{{ $myReview->comment ?? '' }}</textarea>
            <x-ui.button type="submit" variant="primary" size="sm" class="mt-3" x-bind:disabled="rating === 0">{{ __('Publier') }}</x-ui.button>
        </form>
    @endif

    {{-- Liste des avis. --}}
    @if ($count > 0)
        <div class="mt-6 space-y-4">
            @foreach ($reviews->take(10) as $review)
                <div class="bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft p-5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-brand-charcoal">{{ \Illuminate\Support\Str::of($review->user->name)->before(' ') }}</span>
                        <div class="flex items-center" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-brand-yellow' : 'text-ink-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 0 0 .95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 0 0-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 0 0-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 0 0-.36-1.12L2.33 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 0 0 .95-.69L9.05 2.93Z"/></svg>
                            @endfor
                        </div>
                    </div>
                    @if ($review->comment)
                        <p class="mt-2 text-ink-600 text-sm leading-relaxed">{{ $review->comment }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif (! $canReview)
        <p class="mt-6 text-ink-400">{{ __('Aucun avis pour le moment.') }}</p>
    @endif
</section>
