<x-layouts.public :title="__('Vérification de certificat').' — '.config('brand.name')">
    <section class="mx-auto max-w-xl px-4 sm:px-6 py-16 sm:py-20">
        @if ($certificate)
            <div class="bg-white rounded-3xl ring-1 ring-green-200 shadow-card overflow-hidden">
                <div class="bg-gradient-to-br from-green-500 to-green-600 px-8 py-10 text-center">
                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-white/20 ring-4 ring-white/20">
                        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <h1 class="mt-4 text-2xl font-bold text-white">{{ __('Certificat authentique') }}</h1>
                    <p class="mt-1 text-white/85 text-sm">{{ __('Délivré par :brand.', ['brand' => config('brand.name')]) }}</p>
                </div>
                <dl class="p-8 divide-y divide-ink-100">
                    <div class="flex justify-between py-3.5 first:pt-0">
                        <dt class="text-sm text-ink-500">{{ __('Titulaire') }}</dt>
                        <dd class="text-sm font-semibold text-brand-charcoal">{{ $certificate->user->name }}</dd>
                    </div>
                    <div class="flex justify-between py-3.5 gap-6">
                        <dt class="text-sm text-ink-500 shrink-0">{{ __('Formation') }}</dt>
                        <dd class="text-sm font-semibold text-brand-charcoal text-right">{{ $certificate->course->title }}</dd>
                    </div>
                    <div class="flex justify-between py-3.5">
                        <dt class="text-sm text-ink-500">{{ __('Délivré le') }}</dt>
                        <dd class="text-sm font-semibold text-brand-charcoal">{{ $certificate->issued_at->translatedFormat('d F Y') }}</dd>
                    </div>
                    <div class="flex justify-between py-3.5 last:pb-0">
                        <dt class="text-sm text-ink-500">{{ __('Numéro de série') }}</dt>
                        <dd class="text-sm font-mono text-brand-blue-700">{{ $certificate->serial }}</dd>
                    </div>
                </dl>
            </div>
        @else
            <div class="bg-white rounded-3xl ring-1 ring-red-200 shadow-card p-10 text-center">
                <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </span>
                <h1 class="mt-4 text-2xl font-bold text-brand-charcoal">{{ __('Certificat introuvable') }}</h1>
                <p class="mt-2 text-ink-500">{{ __('Aucun certificat ne correspond au numéro « :serial ». Vérifiez votre saisie.', ['serial' => $serial]) }}</p>
                <x-ui.button :href="route('home')" variant="secondary" class="mt-6">{{ __('Retour à l’accueil') }}</x-ui.button>
            </div>
        @endif
    </section>
</x-layouts.public>
