<x-layouts.public :title="__('Mentions légales').' — '.config('brand.name')">
    <x-public.legal-page :heading="__('Mentions légales')" :updatedAt="__('24 juillet 2026')">
        {{-- Coordonnées à compléter et valider par OptiLeads avant la mise en production. --}}
        <h2>{{ __('Éditeur du site') }}</h2>
        <p>
            {{ config('brand.legal_name') }}<br>
            {{ config('brand.city') }}<br>
            {{ __('Adresse e-mail :') }} {{ config('brand.email') }}
        </p>

        <h2>{{ __('Directeur de la publication') }}</h2>
        <p>{{ __('La direction de :brand.', ['brand' => config('brand.legal_name')]) }}</p>

        <h2>{{ __('Hébergement') }}</h2>
        <p>
            {{ __('Le site est hébergé par LWS — Ligne Web Services.') }}
        </p>

        <h2>{{ __('Propriété intellectuelle') }}</h2>
        <p>
            L’ensemble des contenus de la plateforme (textes, vidéos, supports, marques et
            logos) est protégé. Toute reproduction sans autorisation préalable est interdite.
        </p>
    </x-public.legal-page>
</x-layouts.public>
