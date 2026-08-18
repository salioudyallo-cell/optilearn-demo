<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Tâches planifiées. Déclenchées par le cron cPanel unique (chaque minute) qui appelle
 * `artisan schedule:run` (cf. DEPLOY.md). Aucun worker permanent.
 */

// Sauvegarde de la base chaque nuit à 03h00 (heure serveur).
Schedule::command('backup:run --only-db')
    ->dailyAt('03:00')
    ->onOneServer()
    ->withoutOverlapping();

// Nettoyage des anciennes sauvegardes (rétention 14 jours) à 03h30.
Schedule::command('backup:clean')
    ->dailyAt('03:30')
    ->onOneServer()
    ->withoutOverlapping();

// Purge des jobs échoués de plus de 14 jours (table database).
Schedule::command('queue:prune-failed --hours=336')->daily();

// Relance des apprenants inactifs (7 à 30 jours), une fois par jour en journée.
Schedule::command('app:remind-inactive')->dailyAt('09:00');

/*
 * Traitement de la file (driver database, pas de worker permanent sur le mutualisé).
 * Le cron déclenche schedule:run chaque minute, qui lance ici un worker éphémère qui
 * s'arrête dès la file vide (ou au bout de 50 s, avant la minute suivante).
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
