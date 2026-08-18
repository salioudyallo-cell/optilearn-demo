<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quizzes;

use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\Quizzes\Schemas\QuizForm;
use App\Models\Quiz;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ressource technique : un quiz se gère depuis sa leçon (bouton « Configurer le quiz »
 * du relation manager des leçons). N'apparaît pas dans la navigation.
 */
class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'quiz';

    protected static ?string $pluralModelLabel = 'quiz';

    public static function form(Schema $schema): Schema
    {
        return QuizForm::configure($schema);
    }

    /**
     * Un formateur n'atteint que les quiz de ses propres formations.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user !== null && $user->isInstructor()) {
            $query->whereHas('lesson.module.course', fn (Builder $q) => $q->where('instructor_id', $user->getKey()));
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}
