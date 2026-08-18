<x-layouts.public :title="__('Conditions générales d’utilisation').' — '.config('brand.name')">
    <x-public.legal-page :heading="__('Conditions générales d’utilisation')" :updatedAt="__('24 juillet 2026')">
        {{-- Contenu à valider par le service juridique d'OptiLeads avant la mise en production. --}}
        <p>
            Les présentes conditions générales encadrent l’utilisation de la plateforme de
            formation en ligne {{ config('brand.name') }}. En créant un compte, vous acceptez ces
            conditions.
        </p>

        <h2>1. Objet</h2>
        <p>
            La plateforme donne accès à des formations en ligne aux métiers du digital.
            L’accès à une formation est accordé par {{ config('brand.legal_name') }} après une transaction réalisée
            hors plateforme, puis activé au moyen d’un code personnel.
        </p>

        <h2>2. Compte utilisateur</h2>
        <p>
            Vous êtes responsable de l’exactitude des informations fournies et de la
            confidentialité de vos identifiants. Un compte est personnel et ne peut être
            partagé.
        </p>

        <h2>3. Accès aux formations</h2>
        <p>
            L’activation d’un code d’accès ouvre l’accès à la formation correspondante.
            Le contenu est réservé à un usage personnel : sa reproduction ou sa diffusion
            sans autorisation est interdite.
        </p>

        <h2>4. Certificats</h2>
        <p>
            Un certificat est délivré à l’issue d’une formation validée. Il atteste du suivi
            et peut être vérifié publiquement à partir de son numéro de série.
        </p>

        <h2>5. Responsabilité</h2>
        <p>
            {{ config('brand.legal_name') }} met tout en œuvre pour assurer la disponibilité et la qualité des
            contenus, sans garantie d’absence d’interruption. La responsabilité de {{ config('brand.legal_name') }}
            ne saurait être engagée en cas d’usage non conforme de la plateforme.
        </p>

        <h2>6. Modification des conditions</h2>
        <p>
            {{ config('brand.legal_name') }} peut faire évoluer ces conditions. Les utilisateurs sont informés de
            toute modification substantielle.
        </p>
    </x-public.legal-page>
</x-layouts.public>
