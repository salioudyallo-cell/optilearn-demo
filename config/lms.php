<?php

declare(strict_types=1);

/**
 * Reglages metier de la plateforme. Aucune de ces valeurs ne doit etre codee en dur
 * dans un service ou une vue (cf. Definition of Done, CLAUDE.md §15).
 */
return [

    /*
     * Visibilite du site pour les robots. Tant que « indexable » est false, le site
     * est tenu hors des moteurs de recherche ET des robots d'IA : balise meta
     * noindex sur toutes les pages, en-tete HTTP X-Robots-Tag, et robots.txt bloquant
     * tout le monde. A passer a true (SITE_INDEXABLE=true) le jour du lancement.
     */
    'site' => [
        'indexable' => (bool) env('SITE_INDEXABLE', false),
    ],

    /*
     * Instance de démonstration (présentation aux prospects). Quand DEMO_MODE=true :
     * un bandeau « Démonstration » s'affiche, et la commande app:demo-reset est
     * autorisée. À laisser false sur les instances réelles.
     */
    'demo' => [
        'enabled' => (bool) env('DEMO_MODE', false),
    ],

    'access_codes' => [
        // Un code de 10 caracteres se force sans limitation de debit.
        'max_attempts' => (int) env('ACCESS_CODE_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('ACCESS_CODE_DECAY_MINUTES', 60),
    ],

    'certificates' => [
        'serial_prefix' => env('CERTIFICATE_SERIAL_PREFIX', 'OPT'),
        'disk' => env('CERTIFICATES_DISK', 'private'),
    ],

    'courses' => [
        'assets_disk' => env('COURSE_ASSETS_DISK', 'private'),
        'media_disk' => env('MEDIA_DISK', 'public'),
    ],

    /*
     * Hebergement des videos. Deux pilotes interchangeables, choisis par VIDEO_DRIVER :
     *
     *  - « local » : fichiers MP4 sur le serveur (disque prive « videos »), deposes par
     *    FTP. Adapte au demarrage et aux tests (quelques dizaines d'apprenants).
     *  - « bunny » : Bunny Stream, CDN mondial et qualite adaptative. A activer des que
     *    l'audience grandit — aucun contenu a recreer, seul ce reglage change.
     *
     * « delivery » decide QUI envoie les octets une fois l'autorisation verifiee :
     *  - « php »       : Laravel streame le fichier (BinaryFileResponse, supporte les
     *                    requetes Range donc l'avance/recul). Fonctionne partout, mais
     *                    mobilise un worker PHP pendant toute la lecture.
     *  - « sendfile »  : Laravel n'envoie qu'un en-tete et delegue au serveur web, qui
     *                    sert le fichier en statique et libere PHP immediatement. Bien
     *                    plus econome, mais l'en-tete doit etre supporte et active par
     *                    l'hebergeur (a tester en production).
     */
    'video' => [
        'driver' => env('VIDEO_DRIVER', 'local'),
        'disk' => env('VIDEO_DISK', 'videos'),
        // Duree de validite du lien de lecture signe, en secondes (4 h par defaut).
        'signed_url_ttl' => (int) env('VIDEO_SIGNED_URL_TTL', 14400),
        'delivery' => env('VIDEO_DELIVERY', 'php'),
        // En-tete de delegation : X-Sendfile (Apache), X-Accel-Redirect (nginx),
        // X-LiteSpeed-Location (LiteSpeed / LWS).
        'sendfile_header' => env('VIDEO_SENDFILE_HEADER', 'X-Sendfile'),
        // Extensions acceptees pour une video auto-hebergee.
        'allowed_extensions' => ['mp4', 'm4v', 'webm'],
    ],

    /*
     * Paiement (mode Commercial). « offline » = confirmation manuelle par l'admin
     * (Wave/Orange Money/virement/espèces). Un prestataire en ligne se branchera plus
     * tard via une nouvelle implémentation de PaymentGateway, sans toucher au parcours.
     */
    'payment' => [
        'gateway' => env('PAYMENT_GATEWAY', 'offline'),
    ],

    'bunny' => [
        'library_id' => env('BUNNY_LIBRARY_ID'),
        'api_key' => env('BUNNY_API_KEY'),
        'cdn_hostname' => env('BUNNY_CDN_HOSTNAME'),
        'token_security_key' => env('BUNNY_TOKEN_SECURITY_KEY'),
        // Duree de validite d'une URL signee, en secondes (4 h par defaut).
        'signed_url_ttl' => (int) env('BUNNY_SIGNED_URL_TTL', 14400),
    ],

    'contact' => [
        'whatsapp_number' => env('CONTACT_WHATSAPP_NUMBER'),
    ],

    'error_notifications' => [
        // Desactive en local (APP_DEBUG=true) : on ne veut pas d'email a chaque exception
        // pendant le developpement. Active par defaut des que le debug est coupe (prod).
        'enabled' => (bool) env('ERROR_NOTIFICATIONS_ENABLED', ! env('APP_DEBUG', false)),
        'email' => env('ADMIN_ERROR_EMAIL', env('BACKUP_NOTIFICATION_EMAIL', 'admin@opti-leads.com')),
        // Fenetre anti-flood : au plus un email par type d'erreur toutes les N minutes.
        'throttle_minutes' => (int) env('ERROR_NOTIFICATIONS_THROTTLE', 30),
    ],

];
