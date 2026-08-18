<?php

declare(strict_types=1);

namespace App\Filament\Resources\Groups\RelationManagers;

use App\Models\Group;
use App\Models\User;
use App\Services\CourseAssigner;
use App\Services\GroupProgressReport;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Membres du groupe. Ajouter un membre l'inscrit automatiquement aux formations déjà
 * affectées au groupe (réconciliation). Le retrait d'un membre ne révoque PAS ses
 * inscriptions : un accès peut provenir d'un autre groupe ou d'un code, la révocation
 * reste une action explicite (Inscriptions).
 */
class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Membres';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('progress')
                    ->label('Progression')
                    ->badge()
                    ->state(function (User $record): string {
                        /** @var Group $group */
                        $group = $this->getOwnerRecord();
                        $data = app(GroupProgressReport::class)->forMember($record, $group->courses()->get());

                        return $data['percent'].' % · '.$data['courses_done'].'/'.$data['courses_total'].' formation(s)';
                    }),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Ajouter un membre')
                    ->recordSelectSearchColumns(['name', 'email'])
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
