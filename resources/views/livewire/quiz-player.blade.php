<div>
    @php($questions = $quiz->questions->sortBy('position')->values())

    {{-- ============ RÉSULTAT (après correction) ============ --}}
    @if ($submitted)
        <div class="rounded-2xl border p-6 mb-6 {{ $passed ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <p class="text-sm font-medium {{ $passed ? 'text-green-800' : 'text-red-800' }}">
                {{ $passed ? __('Quiz réussi') : __('Quiz non réussi') }}
            </p>
            <p class="mt-1 text-3xl font-bold {{ $passed ? 'text-green-700' : 'text-red-700' }}">{{ $scorePercent }} %</p>
            <p class="mt-1 text-sm text-ink-600">
                {{ __('Score de passage : :n %', ['n' => $quiz->pass_score_pct]) }}
            </p>
        </div>

        <div class="space-y-5">
            @foreach ($questions as $i => $question)
                @php($chosen = $answers[$question->id] ?? null)
                @php($correct = $correctByQuestion[$question->id] ?? null)
                <div class="bg-white rounded-2xl border border-ink-100 p-5">
                    <p class="font-semibold text-brand-charcoal">{{ $i + 1 }}. {{ $question->prompt }}</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($question->options as $option)
                            @php($isCorrect = $option->id === $correct)
                            @php($isChosen = $option->id === $chosen)
                            <li class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg
                                {{ $isCorrect ? 'bg-green-50 text-green-800' : ($isChosen ? 'bg-red-50 text-red-800' : 'text-ink-600') }}">
                                @if ($isCorrect)
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                @elseif ($isChosen)
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                @else
                                    <span class="w-4 h-4 shrink-0"></span>
                                @endif
                                <span>{{ $option->label }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($question->explanation)
                        <p class="mt-3 text-sm text-ink-600 bg-ink-50 rounded-lg px-3 py-2">
                            <span class="font-medium">{{ __('Explication :') }}</span> {{ $question->explanation }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            @if (! $passed && $remainingAttempts > 0)
                <button type="button" wire:click="retry"
                        class="inline-flex items-center px-5 py-2.5 rounded-lg bg-brand-orange-500 text-white font-semibold hover:bg-brand-orange-600 transition">
                    {{ __('Réessayer (:n tentative(s) restante(s))', ['n' => $remainingAttempts]) }}
                </button>
            @elseif (! $passed)
                <p class="text-sm text-ink-600">{{ __('Vous avez atteint le nombre maximal de tentatives.') }}</p>
            @endif
        </div>

    {{-- ============ PASSAGE DU QUIZ (état local Alpine) ============ --}}
    @elseif ($remainingAttempts <= 0)
        <div class="bg-ink-50 rounded-2xl border border-ink-100 p-6 text-center text-ink-600">
            {{ __('Vous avez atteint le nombre maximal de tentatives pour ce quiz.') }}
        </div>
    @else
        <div
            x-data="{
                current: 0,
                total: {{ $questions->count() }},
                answers: {},
                get answeredCount() { return Object.keys(this.answers).length; },
                choose(q, o) { this.answers[q] = o; },
                submit() { $wire.submit(this.answers); },
            }"
            class="bg-white rounded-2xl border border-ink-100 p-6"
        >
            @error('error') <p class="text-sm text-red-600 mb-4">{{ $message }}</p> @enderror
            @if ($error)<p class="text-sm text-red-600 mb-4">{{ $error }}</p>@endif

            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-ink-400">
                    {{ __('Tentatives restantes : :n', ['n' => $remainingAttempts]) }}
                </p>
                <p class="text-sm text-ink-400">
                    <span x-text="current + 1"></span> / {{ $questions->count() }}
                </p>
            </div>

            {{-- Toutes les questions sont chargées ; on n'affiche que la courante (Alpine). --}}
            @foreach ($questions as $i => $question)
                <div x-show="current === {{ $i }}" x-cloak>
                    <p class="font-semibold text-brand-charcoal text-lg">{{ $question->prompt }}</p>
                    <div class="mt-4 space-y-2">
                        @foreach ($question->options as $option)
                            <button type="button"
                                    x-on:click="choose({{ $question->id }}, {{ $option->id }})"
                                    :class="answers[{{ $question->id }}] === {{ $option->id }} ? 'border-brand-orange bg-ink-50' : 'border-ink-200 hover:border-brand-orange/50'"
                                    class="w-full text-left px-4 py-3 rounded-lg border text-sm text-ink-600 transition">
                                {{ $option->label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="mt-6 flex items-center justify-between gap-3">
                <button type="button" x-on:click="current = Math.max(0, current - 1)" x-show="current > 0"
                        class="inline-flex items-center px-4 py-2 rounded-lg border border-ink-200 text-sm font-medium text-ink-600 hover:bg-ink-50 transition">
                    &larr; {{ __('Précédent') }}
                </button>
                <span x-show="current === 0"></span>

                <button type="button" x-on:click="current = Math.min(total - 1, current + 1)" x-show="current < total - 1"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-brand-blue text-white text-sm font-semibold hover:opacity-90 transition ml-auto">
                    {{ __('Suivant') }} &rarr;
                </button>

                <button type="button" x-on:click="submit()" x-show="current === total - 1"
                        :disabled="answeredCount < total"
                        wire:loading.attr="disabled" wire:target="submit"
                        class="inline-flex items-center px-5 py-2.5 rounded-lg bg-brand-orange-500 text-white text-sm font-semibold hover:bg-brand-orange-600 transition disabled:opacity-50 ml-auto">
                    {{ __('Valider le quiz') }}
                </button>
            </div>

            <p x-show="current === total - 1 && answeredCount < total" x-cloak class="mt-3 text-xs text-brand-orange-700 text-right">
                {{ __('Répondez à toutes les questions avant de valider.') }}
            </p>
        </div>
    @endif
</div>
