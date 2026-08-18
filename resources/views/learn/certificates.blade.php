<x-layouts.public :title="__('Mes certificats').' — '.config('brand.name')">
    <section class="bg-hero-mesh border-b border-ink-100">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 py-12">
            <nav class="flex items-center gap-1.5 text-sm text-ink-400 mb-5">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-blue-700 transition">{{ __('Mon espace') }}</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                <span class="text-ink-600">{{ __('Mes certificats') }}</span>
            </nav>
            <h1 class="text-3xl sm:text-4xl font-bold text-brand-charcoal">{{ __('Mes certificats') }}</h1>
            <p class="mt-2 text-ink-500">{{ __('Vos réussites, téléchargeables et vérifiables publiquement.') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 sm:px-6 py-12">
        @if ($certificates->isEmpty())
            <div class="bg-white rounded-3xl ring-1 ring-ink-100 shadow-soft p-12 text-center max-w-lg mx-auto">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-yellow-soft">
                    <svg class="w-7 h-7 text-brand-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" /></svg>
                </span>
                <h2 class="mt-5 text-xl font-semibold text-brand-charcoal">{{ __('Pas encore de certificat') }}</h2>
                <p class="mt-2 text-ink-500">{{ __('Terminez une formation et réussissez son quiz pour obtenir votre premier certificat.') }}</p>
                <x-ui.button :href="route('dashboard')" variant="primary" class="mt-6">{{ __('Reprendre mes formations') }}</x-ui.button>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($certificates as $certificate)
                    <div class="bg-white rounded-2xl ring-1 ring-ink-100 shadow-soft p-5 sm:p-6 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-blue-50 text-brand-blue-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-brand-charcoal truncate">{{ $certificate->course->title }}</h2>
                                <p class="mt-0.5 text-sm text-ink-400">
                                    {{ __('Délivré le :date', ['date' => $certificate->issued_at->translatedFormat('d F Y')]) }}
                                    · <span class="font-mono text-ink-500">{{ $certificate->serial }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <x-ui.button :href="route('certificate.verify', $certificate->serial)" variant="ghost" size="sm">{{ __('Vérifier') }}</x-ui.button>
                            <x-ui.button :href="route('certificate.download', $certificate)" variant="primary" size="sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                {{ __('Télécharger') }}
                            </x-ui.button>
                        </div>

                        {{-- Partage : preuve sociale et acquisition organique. --}}
                        <div class="w-full mt-2 pt-4 border-t border-ink-100 flex flex-wrap items-center gap-3">
                            <span class="text-xs font-medium text-ink-400">{{ __('Partager mon certificat') }}</span>
                            <x-share-certificate :certificate="$certificate" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
