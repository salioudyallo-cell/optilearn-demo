<div>
    @php($type = $lesson->type->value)

    {{-- ================= VIDÉO ================= --}}
    @if ($type === 'video')
        <div
            x-data="lessonVideo({
                lessonId: {{ $lessonId }},
                startAt: {{ $position }},
                videoUrlEndpoint: '{{ route('learn.video-url', $lesson) }}',
            })"
            x-init="init()"
            class="space-y-4"
        >
            <div class="aspect-video rounded-2xl overflow-hidden bg-brand-navy relative">
                {{-- Lecteur distant (Bunny) : iframe alimentée par une URL signée
                     obtenue côté serveur ; le bunny_video_id n'apparaît jamais dans
                     le HTML (§7.4). --}}
                <template x-if="playerUrl && playerKind === 'iframe'">
                    <iframe :src="playerUrl" class="w-full h-full" loading="lazy"
                            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen></iframe>
                </template>

                {{-- Vidéo auto-hébergée : lecteur natif alimenté par un lien signé et
                     temporaire ; le chemin du fichier n'est jamais exposé. --}}
                <template x-if="playerUrl && playerKind === 'file'">
                    <video :src="playerUrl" class="w-full h-full" controls playsinline
                           preload="metadata" controlslist="nodownload"
                           x-init="bindNativeVideo($el)"></video>
                </template>

                <template x-if="! playerUrl">
                    <div class="w-full h-full flex flex-col items-center justify-center text-white/80 text-center px-6">
                        <svg class="w-12 h-12 mb-3 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        <p x-text="statusMessage"></p>
                    </div>
                </template>
            </div>

            {{-- File d'attente hors ligne : indicateur discret. --}}
            <p x-show="pendingSync" x-cloak class="text-xs text-brand-orange-600-dark">
                {{ __('Progression en attente de synchronisation…') }}
            </p>
        </div>

    {{-- ================= TEXTE (markdown) ================= --}}
    @elseif ($type === 'text')
        <article class="prose-lesson max-w-none">
            {!! \Illuminate\Support\Str::markdown($lesson->content ?? '') !!}
        </article>

    {{-- ================= PDF ================= --}}
    @elseif ($type === 'pdf')
        <div class="bg-white rounded-2xl border border-ink-100 p-6 text-center">
            <svg class="w-12 h-12 mx-auto text-brand-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            <p class="mt-3 text-ink-600">{{ __('Ce support est disponible en téléchargement.') }}</p>
            <a href="{{ route('learn.pdf', $lesson) }}"
               class="mt-4 inline-flex items-center justify-center px-5 py-3 rounded-lg bg-brand-orange-500 text-white font-semibold hover:bg-brand-orange-600 transition">
                {{ __('Télécharger le PDF') }}
            </a>
        </div>

    {{-- ================= QUIZ (phase 4) ================= --}}
    @elseif ($type === 'quiz')
        <div class="bg-ink-50 rounded-2xl border border-ink-100 p-6 text-center text-ink-600">
            {{ __('Le quiz de cette leçon sera disponible prochainement.') }}
        </div>
    @endif

    {{-- ================= Marquer terminé ================= --}}
    <div class="mt-6 flex items-center gap-3">
        @if ($completed)
            <span class="inline-flex items-center gap-2 text-sm font-medium text-green-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                {{ __('Leçon terminée') }}
            </span>
        @else
            <button type="button" wire:click="markCompleted"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-brand-blue text-white font-semibold hover:opacity-90 transition"
                    wire:loading.attr="disabled" wire:target="markCompleted">
                {{ __('Marquer comme terminée') }}
            </button>
        @endif
    </div>
</div>
