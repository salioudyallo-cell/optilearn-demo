<x-layouts.public :title="__('Politique de confidentialité').' — '.config('brand.name')">
    <x-public.legal-page :heading="__('Politique de confidentialité')" :updatedAt="__('24 juillet 2026')">
        {{-- Contenu à valider par le service juridique d'OptiLeads avant la mise en production. --}}
        <p>
            Cette politique décrit les données que nous collectons, leur usage et vos droits.
        </p>

        <h2>{{ __('Données collectées') }}</h2>
        <p>
            Lors de la création de votre compte, nous collectons votre nom, votre adresse
            e-mail, votre numéro de téléphone, votre pays et, le cas échéant, votre
            entreprise. Nous enregistrons également votre progression dans les formations.
        </p>

        <h2>{{ __('Utilisation des données') }}</h2>
        <p>
            Vos données servent à gérer votre accès aux formations, à suivre votre
            progression, à délivrer vos certificats et à vous contacter au sujet de votre
            accès. Elles ne sont pas revendues à des tiers.
        </p>

        <h2>{{ __('Conservation') }}</h2>
        <p>
            Vos données sont conservées le temps nécessaire à la fourniture du service, puis
            supprimées ou anonymisées.
        </p>

        <h2>{{ __('Vos droits') }}</h2>
        <p>
            Vous pouvez demander l’accès à vos données, leur rectification ou leur
            suppression. La suppression de votre compte entraîne l’effacement de vos données
            personnelles. Pour toute demande, écrivez à {{ config('brand.email') }}.
        </p>

        <h2>{{ __('Cookies') }}</h2>
        <p>
            La plateforme utilise uniquement les cookies nécessaires à son fonctionnement
            (session, sécurité). Aucun cookie publicitaire n’est déposé.
        </p>
    </x-public.legal-page>
</x-layouts.public>
