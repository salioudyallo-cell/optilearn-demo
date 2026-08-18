<?php

declare(strict_types=1);

namespace App\Filament\Resources\Groups\RelationManagers;

use App\Models\Group;
use App\Services\CourseAssigner;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Formations affectées au groupe. Affecter une formation inscrit immédiatement tous les
 * membres. Retirer une formation ne révoque pas les inscriptions déjà accordées.
 */
class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    protected static ?string $title = 'Formations affectées';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Formation')->searchable(),
                TextColumn::make('level')->label('Niveau')->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Affecter une formation')
                    ->recordSelectSearchColumns(['title'])
                    ->after(function (): void {
                        /** @var Group $group */
                        $group = $this->getOwnerRecord();
                        app(CourseAssigner::class)->reconcileGroup($group);
                    }),
            ])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DetachBulkAction::make()]),
            ]);
    }
}
