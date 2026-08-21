<?php

declare(strict_types=1);

/*
 * Identite de marque de l'instance (marque blanche).
 *
 * >>> FORK « OptiLearn » (demo produit presentee aux prospects) <<<
 * OptiLearn est le PRODUIT e-learning d'OptiLeads, vendu et mis aux couleurs de chaque
 * client. Cette instance de demo le presente sous la charte OptiLeads (bleu/orange).
 * Le .env peut surcharger n'importe quelle valeur (utile pour deriver une instance
 * client) ; sans variable BRAND_*, le site est deja « OptiLearn ».
 *
 * Couleurs : custom = false par defaut -> on garde la charte compilee dans app.css
 * (OptiLeads). Une instance client definit BRAND_COLOR_PRIMARY pour re-thematiser.
 */
return [

    // Nom affiche. Repris de APP_NAME (defaut « OptiLearn » dans config/app.php).
    'name' => env('APP_NAME', 'OptiLearn'),

    // Nom court (« Reglez avec X », copyright, contact).
    'short_name' => env('BRAND_SHORT_NAME', 'OptiLearn'),

    // Raison sociale pour les pages legales.
    'legal_name' => env('BRAND_LEGAL_NAME', 'OptiLearn'),

    // Accroche courte (meta description par defaut, pied de page).
    'tagline' => env('BRAND_TAGLINE', 'La plateforme de formation en ligne clé en main, pensée pour l’Afrique de l’Ouest.'),

    // Signature de marque (bas de page a droite).
    'slogan' => env('BRAND_SLOGAN', 'Apprendre, certifier, progresser.'),

    // Coordonnees affichees.
    'email' => env('BRAND_EMAIL', 'contact@opti-leads.com'),
    'city' => env('BRAND_CITY', 'Dakar, Sénégal'),
    'region' => env('BRAND_REGION', 'Afrique de l’Ouest'),

    /*
     * Logo. Vide => embleme colore + nom en toutes lettres (aucun fichier a produire).
     */
    'logo_image' => env('BRAND_LOGO_IMAGE', ''),
    'logo_image_white' => env('BRAND_LOGO_IMAGE_WHITE', ''),

    // Favicon (onglet du navigateur). Accepte .png ou .svg.
    'favicon' => env('BRAND_FAVICON', 'images/favicon-optilearn.svg'),

    /*
     * Couleurs. custom = false par defaut : on conserve la charte OptiLeads compilee.
     * Une instance client active le re-theme en definissant BRAND_COLOR_PRIMARY.
     * Les valeurs ci-dessous servent aussi a <meta theme-color>.
     */
    'colors' => [
        'custom' => filled(env('BRAND_COLOR_PRIMARY')),
        'primary' => env('BRAND_COLOR_PRIMARY', '#1a56d6'),
        'accent' => env('BRAND_COLOR_ACCENT', '#f97600'),
        'highlight' => env('BRAND_COLOR_HIGHLIGHT', '#ffc101'),
    ],

];
