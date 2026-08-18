<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessCodes\Tables;

use App\Models\AccessCode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccessCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('course.title')
                    ->label('Formation')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('remaining')
                    ->label('Restant')
                    ->state(fn (AccessCode $record): string => $record->remainingUses().' / '.$record->max_uses)
                    ->badge()
                    ->color(fn (AccessCode $record): string => $record->remainingUses() > 0 ? 'success' : 'danger'),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->label('Formation')
                    ->relationship('course', 'title'),

                Filter::make('active')
                    ->label('Actifs uniquement')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('exhausted')
                    ->label('Épuisés')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('used_count', '>=', 'max_uses')),
            ])
            ->recordActions([
                // Desactivation immediate sans passer par le formulaire d'edition.
                Action::make('toggleActive')
                    ->label(fn (AccessCode $record): string => $record->is_active ? 'Désactiver' : 'Réactiver')
                    ->icon(fn (AccessCode $record): string => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (AccessCode $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (AccessCode $record) => $record->update(['is_active' => ! $record->is_active])),

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
