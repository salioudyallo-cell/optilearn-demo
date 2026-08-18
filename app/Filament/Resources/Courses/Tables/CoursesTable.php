<?php

declare(strict_types=1);

namespace App\Filament\Resources\Courses\Tables;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->subtitle),

                TextColumn::make('instructor.name')
                    ->label('Formateur')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('level')
                    ->label('Niveau')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (CourseStatus $state): string => match ($state) {
                        CourseStatus::Draft => 'gray',
                        CourseStatus::Published => 'success',
                        CourseStatus::Archived => 'warning',
                    }),

                TextColumn::make('price_fcfa')
                    ->label('Prix')
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', ' ').' FCFA')
                    ->sortable(),

                TextColumn::make('modules_count')
                    ->label('Modules')
                    ->counts('modules')
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Publiée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(CourseStatus::class),

                SelectFilter::make('level')
                    ->label('Niveau')
                    ->options(CourseLevel::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
