<?php

declare(strict_types=1);

/*
 * Identite de marque de l'instance (marque blanche).
 *
 * >>> FORK « Nimba Académie » <<<
 * Ce dépôt est une instance dédiée : les valeurs par defaut ci-dessous sont celles de
 * Nimba. Le .env peut toujours surcharger (utile pour une future instance derivee), mais
 * sans aucune variable BRAND_*, le site est deja entierement « Nimba Académie ».
 *
 * Couleurs : l'echelle complete (50..900) est generee et injectee au rendu (voir
 * App\Support\BrandPalette et <x-brand-theme />). Le re-theme est actif par defaut ici.
 */
return [

    // Nom affiche. Repris de APP_NAME (defaut « Nimba Académie » dans config/app.php).
    'name' => env('APP_NAME', 'Nimba Académie'),

    // Nom court de l'entite (« Reglez avec X », copyright, contact).
    'short_name' => env('BRAND_SHORT_NAME', 'Nimba'),

    // Raison sociale pour les pages legales.
    'legal_name' => env('BRAND_LEGAL_NAME', 'Nimba Académie'),

    // Accroche courte (meta description par defaut, pied de page).
    'tagline' => env('BRAND_TAGLINE', 'Montez en compétences, où que vous soyez.'),

    // Signature de marque (bas de page a droite).
    'slogan' => env('BRAND_SLOGAN', 'Apprendre, progresser, réussir.'),

    // Coordonnees affichees.
    'email' => env('BRAND_EMAIL', 'contact@nimba-academie.com'),
    'city' => env('BRAND_CITY', 'Abidjan · Dakar'),
    'region' => env('BRAND_REGION', 'Afrique de l’Ouest'),

    /*
     * Logo. Vide => embleme colore (couleurs de marque) + nom en toutes lettres : aucun
     * fichier a produire. Pour un vrai logo, pointer un fichier de public/.
     */
    'logo_image' => env('BRAND_LOGO_IMAGE', ''),
    'logo_image_white' => env('BRAND_LOGO_IMAGE_WHITE', ''),

    // Favicon (onglet du navigateur). Accepte .png ou .svg.
    'favicon' => env('BRAND_FAVICON', 'images/favicon-nimba.svg'),

    /*
     * Couleurs de marque Nimba : vert profond + ambre + or. L'echelle est generee a partir
     * de ces trois valeurs. Le re-theme est actif par defaut (custom = true).
     */
    'colors' => [
        'custom' => filled(env('BRAND_COLOR_PRIMARY', '#0f6e5c')),
        'primary' => env('BRAND_COLOR_PRIMARY', '#0f6e5c'),
        'accent' => env('BRAND_COLOR_ACCENT', '#e0851e'),
        'highlight' => env('BRAND_COLOR_HIGHLIGHT', '#f5b841'),
    ],

];
