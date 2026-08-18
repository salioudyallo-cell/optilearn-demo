<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlatformMode;
use App\Enums\UserRole;
use App\Facades\Platform;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * Réinitialise l'instance de démonstration : base repartie de zéro + jeu de données de
 * démo + compte admin. À lancer périodiquement pour effacer ce que les prospects ont
 * saisi.
 *
 * GARDE-FOU : refuse de s'exécuter si DEMO_MODE n'est pas activé, pour ne JAMAIS effacer
 * une instance réelle.
 */
class DemoReset extends Command
{
    protected $signature = 'app:demo-reset {--mode=enterprise : Mode par défaut après réinitialisation (enterprise|commercial)}';

    protected $description = 'Réinitialise l’instance de démonstration (base + données + admin). DEMO_MODE requis.';

    public function handle(): int
    {
        if (! config('lms.demo.enabled')) {
            $this->error('Refusé : DEMO_MODE n’est pas activé. Cette commande n’est autorisée que sur une instance de démonstration.');

            return self::FAILURE;
        }

        $this->info('Réinitialisation de la démonstration…');

        Artisan::call('migrate:fresh', ['--force' => true], $this->getOutput());
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true], $this->getOutput());

        $admin = new User;
        $admin->name = 'Administrateur Démo';
        $admin->email = 'admin@demo.opti-leads.com';
        $admin->password = Hash::make('demo1234');
        $admin->role = UserRole::Admin;
        $admin->email_verified_at = now();
        $admin->save();

        $mode = $this->option('mode') === 'commercial' ? PlatformMode::Commercial : PlatformMode::Enterprise;
        Platform::setMode($mode);

        Artisan::call('optimize:clear');

        $this->newLine();
        $this->info('Démonstration réinitialisée.');
        $this->line('  Admin : admin@demo.opti-leads.com / demo1234');
        $this->line('  Mode  : '.$mode->value);

        return self::SUCCESS;
    }
}
