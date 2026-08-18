<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Models\QuizQuestion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Questions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('prompt')
                    ->label('Question')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('explanation')
                    ->label('Explication (affichée après correction)')
                    ->rows(2)
                    ->columnSpanFull(),

                // Les options se saisissent d'un bloc ; la bonne reponse est choisie au sein
                // du meme ecran. Sauvegarde vers quiz_options via ->relationship().
                Repeater::make('options')
                    ->label('Réponses proposées')
                    ->relationship()
                    ->schema([
                        TextInput::make('label')
                            ->label('Réponse')
                            ->required()
                            ->columnSpan(3),

                        Radio::make('is_correct')
                            ->label('Bonne réponse')
                            ->boolean()
                            ->default(false)
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->minItems(2)
                    ->maxItems(6)
                    ->defaultItems(4)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prompt')
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('position')->label('#')->badge(),
                TextColumn::make('prompt')->label('Question')->wrap()->searchable(),
                TextColumn::make('options_count')->label('Réponses')->counts('options'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter une question')
                    ->mutateDataUsing(function (array $data): array {
                        $data['position'] = (int) QuizQuestion::query()
                            ->where('quiz_id', $this->getOwnerRecord()->getKey())
                            ->max('position') + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
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
