@props(['heading', 'updatedAt' => null])

{{-- Gabarit commun aux pages legales : titre + contenu en prose. --}}
<section class="bg-hero-mesh border-b border-ink-100">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 py-14">
        <h1 class="text-3xl sm:text-4xl font-bold text-brand-charcoal">{{ $heading }}</h1>
        @if ($updatedAt)
            <p class="mt-3 inline-flex items-center gap-1.5 text-sm text-ink-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ __('Dernière mise à jour : :date', ['date' => $updatedAt]) }}
            </p>
        @endif
    </div>
</section>

<section class="mx-auto max-w-3xl px-4 sm:px-6 py-12">
    <div class="space-y-5 text-ink-600 leading-relaxed [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-brand-charcoal [&_h2]:mt-10 [&_h2]:mb-2 [&_p]:mt-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-1 [&_a]:text-brand-blue-700 [&_a]:font-medium [&_a]:underline [&_a]:underline-offset-2">
        {{ $slot }}
    </div>
</section>
