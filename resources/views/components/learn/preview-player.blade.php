@props(['lesson'])

{{--
    Aperçu public d'une leçon d'essai : lecture réelle, sans compte.
    C'est le levier de conversion — un badge « Aperçu gratuit » qui n'ouvre rien
    ne sert à rien. LessonPolicy::view() autorise déjà l'invité sur une leçon
    is_preview d'un cours publié ; aucune progression n'est enregistrée ici.
--}}
<div
    x-data="lessonVideo({
        lessonId: {{ $lesson->id }},
        startAt: 0,
        videoUrlEndpoint: '{{ route('learn.video-url', $lesson) }}',
        guest: true,
    })"
    x-init="init()"
    class="space-y-4"
>
    <div class="aspect-video rounded-2xl overflow-hidden bg-brand-navy relative">
        <template x-if="playerUrl && playerKind === 'iframe'">
            <iframe :src="playerUrl" class="w-full h-full" loading="lazy"
                    allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                    allowfullscreen></iframe>
        </template>

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

    {{-- Appel à l'action juste sous la vidéo : le moment où l'intérêt est le plus fort. --}}
    <div class="rounded-2xl border border-ink-100 bg-ink-50/60 p-5 sm:p-6">
        <p class="text-sm font-semibold text-brand-orange-600">{{ __('Aperçu gratuit') }}</p>
        <h2 class="mt-1 text-lg font-bold text-brand-charcoal">
            {{ __('Vous venez de voir une leçon de cette formation.') }}
        </h2>
        <p class="mt-2 text-ink-600">
            {{ __('Activez votre accès pour suivre l’intégralité du programme, passer le quiz et obtenir votre certificat.') }}
        </p>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-ui.button :href="route('register')" variant="primary">
                {{ __('Demander l’accès') }}
            </x-ui.button>
            <x-ui.button :href="route('catalog.show', $lesson->module->course)" variant="secondary">
                {{ __('Voir le programme complet') }}
            </x-ui.button>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-blue hover:underline">
                {{ __('J’ai déjà un compte') }}
            </a>
        </div>
    </div>
</div>
