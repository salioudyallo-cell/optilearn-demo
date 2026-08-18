<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\LessonType;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Lesson;
use App\Models\Quiz;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'Leçons';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre de la leçon')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('type')
                    ->label('Type de contenu')
                    ->options(LessonType::class)
                    ->default(LessonType::Video)
                    ->required()
                    ->live(),

                Toggle::make('is_preview')
                    ->label('Leçon d’essai (accès libre)')
                    ->helperText('Visible sans inscription depuis la fiche formation.'),

                // --- Vidéo auto-hébergée (pilote « local ») ---
                // Les fichiers sont déposés par FTP dans storage/app/videos : les gros
                // fichiers dépassent les limites d'upload PHP du mutualisé.
                Select::make('video_path')
                    ->label('Fichier vidéo')
                    ->options(fn (): array => self::availableVideoFiles())
                    ->searchable()
                    ->helperText('Déposez le fichier MP4 par FTP dans storage/app/videos, puis sélectionnez-le ici. Jamais accessible par une URL directe.')
                    ->visible(fn (Get $get): bool => self::usesLocalVideo() && $get('type') === LessonType::Video->value)
                    ->required(fn (Get $get): bool => self::usesLocalVideo() && $get('type') === LessonType::Video->value)
                    ->columnSpanFull(),

                // --- Vidéo (Bunny Stream) ---
                TextInput::make('bunny_video_id')
                    ->label('Identifiant vidéo Bunny')
                    ->helperText('Collez l’identifiant de la vidéo hébergée sur Bunny Stream. Jamais exposé au public : le lecteur reçoit une URL signée.')
                    ->visible(fn (Get $get): bool => ! self::usesLocalVideo() && $get('type') === LessonType::Video->value)
                    ->required(fn (Get $get): bool => ! self::usesLocalVideo() && $get('type') === LessonType::Video->value)
                    ->columnSpanFull(),

                TextInput::make('duration_seconds')
                    ->label('Durée (secondes)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->visible(fn (Get $get): bool => $get('type') === LessonType::Video->value),

                // --- Texte (markdown) ---
                Textarea::make('content')
                    ->label('Contenu')
                    ->rows(12)
                    ->helperText('Markdown accepté.')
                    ->visible(fn (Get $get): bool => $get('type') === LessonType::Text->value)
                    ->required(fn (Get $get): bool => $get('type') === LessonType::Text->value)
                    ->columnSpanFull(),

                // --- PDF (stocké hors public, servi après autorisation) ---
                FileUpload::make('asset_path')
                    ->label('Document PDF')
                    ->acceptedFileTypes(['application/pdf'])
                    ->disk(config('lms.courses.assets_disk'))
                    ->directory('courses/assets')
                    ->visible(fn (Get $get): bool => $get('type') === LessonType::Pdf->value)
                    ->required(fn (Get $get): bool => $get('type') === LessonType::Pdf->value)
                    ->columnSpanFull(),

                // --- Quiz ---
                Textarea::make('quiz_note')
                    ->label('Quiz')
                    ->default('Les questions du quiz se configurent après la création de la leçon (module Quiz, phase 4).')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => $get('type') === LessonType::Quiz->value)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->badge(),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                IconColumn::make('is_preview')
                    ->label('Essai')
                    ->boolean(),

                TextColumn::make('duration_seconds')
                    ->label('Durée')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? gmdate('i:s', $state) : '—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une leçon')
                    ->mutateDataUsing(function (array $data): array {
                        // La position suit l'ordre de creation dans le module.
                        $data['position'] = (int) Lesson::query()
                            ->where('module_id', $this->getOwnerRecord()->getKey())
                            ->max('position') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                // Pour une lecon de type quiz : cree le quiz au besoin puis ouvre son editeur.
                Action::make('configureQuiz')
                    ->label('Configurer le quiz')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->visible(fn (Lesson $record): bool => $record->type === LessonType::Quiz)
                    ->url(function (Lesson $record): string {
                        $quiz = Quiz::query()->firstOrCreate(['lesson_id' => $record->getKey()]);

                        return QuizResource::getUrl('edit', ['record' => $quiz]);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Le pilote video actif est-il l'auto-hebergement ?
     */
    private static function usesLocalVideo(): bool
    {
        return config('lms.video.driver') === 'local';
    }

    /**
     * Fichiers video presents sur le disque, deposes par FTP.
     *
     * @return array<string, string>
     */
    private static function availableVideoFiles(): array
    {
        $disk = Storage::disk(config('lms.video.disk'));

        if (! $disk->exists('/')) {
            return [];
        }

        /** @var list<string> $extensions */
        $extensions = config('lms.video.allowed_extensions', []);

        $files = collect($disk->allFiles())
            ->filter(fn (string $path): bool => in_array(
                strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                $extensions,
                strict: true,
            ))
            ->sort()
            ->values();

        return $files->mapWithKeys(fn (string $path): array => [
            $path => $path.' ('.self::humanSize($disk->size($path)).')',
        ])->all();
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1).' Go';
        }

        return round($bytes / 1048576).' Mo';
    }
}
