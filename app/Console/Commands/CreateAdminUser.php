<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Cree un compte administrateur (acces au back-office Filament).';

    public function handle(): int
    {
        $name = text(
            label: 'Nom complet',
            required: true,
        );

        $email = text(
            label: 'Adresse e-mail',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                filter_var($value, FILTER_VALIDATE_EMAIL) === false => 'Adresse e-mail invalide.',
                User::where('email', $value)->exists() => 'Un compte existe deja avec cette adresse.',
                default => null,
            },
        );

        $pw = password(
            label: 'Mot de passe (8 caracteres minimum)',
            required: true,
            validate: fn (string $value): ?string => strlen($value) < 8 ? 'Au moins 8 caracteres.' : null,
        );

        if ($pw !== password(label: 'Confirmez le mot de passe', required: true)) {
            $this->error('Les mots de passe ne correspondent pas.');

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = $pw; // le cast 'hashed' du modele hache automatiquement.
        $user->role = UserRole::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Compte administrateur cree : {$user->email}");

        return self::SUCCESS;
    }
}
