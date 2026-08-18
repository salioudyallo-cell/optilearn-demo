<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessCodes\Schemas;

use App\Models\AccessCode;
use App\Models\Course;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AccessCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Code d’accès')
                    ->columns(2)
                    ->schema([
                        Select::make('course_id')
                            ->label('Formation')
                            ->relationship(
                                'course',
                                'title',
                                // Un formateur ne genere de codes que pour ses propres formations.
                                fn (Builder $query) => self::scopeCoursesToOwner($query),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('code')
                            ->label('Code')
                            ->helperText('Généré automatiquement. Format à 10 caractères sans ambiguïté.')
                            ->default(fn () => AccessCode::generateUniqueCode())
                            ->required()
                            ->readOnly()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10),

                        TextInput::make('label')
                            ->label('Libellé interne')
                            ->helperText('Ex. « Cohorte SEO janvier — Société X ». Jamais montré à l’apprenant.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Conditions d’utilisation')
                    ->columns(2)
                    ->schema([
                        TextInput::make('max_uses')
                            ->label('Nombre d’utilisations')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),

                        DateTimePicker::make('expires_at')
                            ->label('Expire le')
                            ->seconds(false)
                            ->helperText('Laisser vide pour un code sans date d’expiration.'),

                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Désactivez pour bloquer immédiatement toute nouvelle activation.'),
                    ]),
            ]);
    }

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    private static function scopeCoursesToOwner(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user !== null && $user->isInstructor()) {
            $query->where('instructor_id', $user->getKey());
        }

        return $query;
    }
}
