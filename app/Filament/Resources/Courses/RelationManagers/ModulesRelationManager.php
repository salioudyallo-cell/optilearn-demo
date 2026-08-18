<?php

declare(strict_types=1);

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Modules\ModuleResource;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Modules';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Titre du module')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position')
            // Le glisser-deposer met a jour la colonne position.
            ->reorderable('position')
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->badge(),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable(),

                TextColumn::make('lessons_count')
                    ->label('Leçons')
                    ->counts('lessons'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un module')
                    // La position suit l'ordre de creation dans le cours.
                    ->mutateDataUsing(function (array $data): array {
                        // La position suit l'ordre de creation dans le cours.
                        $data['position'] = (int) Module::query()
                            ->where('course_id', $this->getOwnerRecord()->getKey())
                            ->max('position') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('lessons')
                    ->label('Leçons')
                    ->icon(Heroicon::OutlinedListBullet)
                    ->url(fn (Module $record): string => ModuleResource::getUrl('edit', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
