<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules;

use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\Modules\Schemas\ModuleForm;
use App\Models\Module;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ressource technique : les modules se gèrent depuis la formation (relation manager).
 * Elle n'apparaît pas dans la navigation ; sa page d'édition sert à gérer les leçons.
 */
class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'module';

    protected static ?string $pluralModelLabel = 'modules';

    public static function form(Schema $schema): Schema
    {
        return ModuleForm::configure($schema);
    }

    /**
     * Un formateur ne peut atteindre que les modules de ses propres formations.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user !== null && $user->isInstructor()) {
            $query->whereHas('course', fn (Builder $q) => $q->where('instructor_id', $user->getKey()));
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditModule::route('/{record}/edit'),
        ];
    }
}
