<?php

declare(strict_types=1);

use App\Enums\PlatformMode;

/**
 * Configuration des modes de plateforme.
 *
 * Un mode est un PRÉRÉGLAGE de capacités. Le code applicatif ne teste jamais le mode :
 * il interroge une capacité via Platform::allows('...'). Pour ajouter un modèle
 * économique, il suffit d'ajouter un cas à PlatformMode et son préréglage ci-dessous —
 * aucune autre partie du code n'est à modifier.
 */
return [

    /*
     * Mode par défaut si aucun réglage n'est encore enregistré en base.
     * Défaut « enterprise » : posture sûre — aucun prix ni paiement exposé.
     */
    'default_mode' => env('PLATFORM_MODE', PlatformMode::Enterprise->value),

    /*
     * Catalogue de toutes les capacités connues, avec un libellé pour l'administration.
     * Une capacité absente d'un préréglage est considérée comme désactivée.
     */
    'capabilities' => [
        'public_catalog' => 'Catalogue public (visible sans connexion)',
        'pricing' => 'Affichage des tarifs',
        'cart' => 'Panier',
        'online_payment' => 'Paiement en ligne',
        'promotions' => 'Codes promo et promotions',
        'reviews' => 'Avis et notes',
        'self_registration' => 'Inscription libre (auto-création de compte)',
        'organizations' => 'Gestion des organisations',
        'groups' => 'Groupes d’apprenants',
        'assignments' => 'Affectation de formations (individuelle ou par groupe)',
        'bulk_import' => 'Import massif d’apprenants (CSV/Excel)',
        'invitations' => 'Invitation d’apprenants par e-mail',
        'hr_dashboards' => 'Tableaux de bord et rapports (RH / administrateur)',
    ],

    /*
     * Préréglages : capacités activées par mode. Tout ce qui n'est pas listé est désactivé.
     */
    'presets' => [

        PlatformMode::Commercial->value => [
            'public_catalog',
            'pricing',
            'cart',
            'online_payment',
            'promotions',
            'reviews',
            'self_registration',
        ],

        PlatformMode::Enterprise->value => [
            'organizations',
            'groups',
            'assignments',
            'bulk_import',
            'invitations',
            'hr_dashboards',
        ],
    ],
];
