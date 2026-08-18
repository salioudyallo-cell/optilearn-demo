<?php

declare(strict_types=1);

namespace App\Filament\Resources\Courses\Schemas;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Présentation')
                    ->description('Ces informations sont visibles publiquement sur la fiche formation.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            // Le slug se genere depuis le titre a la creation, puis reste modifiable.
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Identifiant d’URL')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Utilisé dans l’adresse : /formations/mon-identifiant.'),

                        TextInput::make('subtitle')
                            ->label('Sous-titre')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(8)
                            ->helperText('Markdown accepté.')
                            ->columnSpanFull(),

                        FileUpload::make('cover_path')
                            ->label('Image de couverture')
                            ->image()
                            ->disk(config('lms.courses.media_disk'))
                            ->directory('courses/covers')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),

                Section::make('Paramètres')
                    ->columns(2)
                    ->schema([
                        Select::make('level')
                            ->label('Niveau')
                            ->options(CourseLevel::class)
                            ->default(CourseLevel::Debutant)
                            ->required(),

                        TextInput::make('price_fcfa')
                            ->label('Prix')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->suffix('FCFA')
                            ->helperText('Affiché en vitrine. Le paiement se fait hors plateforme.'),

                        TextInput::make('duration_minutes')
                            ->label('Durée estimée (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        // Reserve a l'administrateur : un formateur reste proprietaire de ses cours.
                        Select::make('instructor_id')
                            ->label('Formateur')
                            ->relationship('instructor', 'name', fn ($query) => $query->whereIn('role', ['instructor', 'admin']))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                            ->dehydrated(),
                    ]),

                Section::make('Publication')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Statut')
                            ->options(CourseStatus::class)
                            ->default(CourseStatus::Draft)
                            ->required()
                            ->live()
                            // La date de publication est posee au premier passage en « Publiée ».
                            ->afterStateUpdated(function (CourseStatus|string|null $state, Get $get, Set $set): void {
                                $value = $state instanceof CourseStatus ? $state->value : $state;

                                if ($value === CourseStatus::Published->value && blank($get('published_at'))) {
                                    $set('published_at', now());
                                }
                            }),

                        DateTimePicker::make('published_at')
                            ->label('Date de publication')
                            ->seconds(false),
                    ]),
            ]);
    }
}
