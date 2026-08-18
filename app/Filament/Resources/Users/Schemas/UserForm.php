<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Facades\Platform;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Adresse e-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(30),

                        TextInput::make('company')
                            ->label('Entreprise')
                            ->maxLength(255),

                        Select::make('country')
                            ->label('Pays')
                            ->options([
                                'SN' => 'Sénégal',
                                'CI' => 'Côte d’Ivoire',
                                'ML' => 'Mali',
                            ]),

                        Select::make('role')
                            ->label('Rôle')
                            ->options(UserRole::class)
                            ->default(UserRole::Learner)
                            ->required(),

                        // Rattachement à une organisation (mode Entreprise). Masqué
                        // quand la capacité « organizations » est inactive.
                        Select::make('organization_id')
                            ->label('Organisation')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => Platform::allows('organizations')),
                    ]),

                Section::make('Accès')
                    ->schema([
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            // Le hachage est assure par le cast « hashed » du modele User.
                            // On ne persiste ce champ que s'il est renseigne (edition = inchange sinon).
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('À l’édition, laissez vide pour conserver le mot de passe actuel.'),
                    ]),
            ]);
    }
}
