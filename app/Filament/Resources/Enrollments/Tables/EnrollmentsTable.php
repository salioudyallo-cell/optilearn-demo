<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enrollments\Tables;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Apprenant')
                    ->description(fn (Enrollment $record): ?string => $record->user?->email)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.title')
                    ->label('Formation')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (EnrollmentStatus $state): string => match ($state) {
                        EnrollmentStatus::Active => 'success',
                        EnrollmentStatus::Expired => 'warning',
                        EnrollmentStatus::Revoked => 'danger',
                    }),

                TextColumn::make('source')
                    ->label('Origine')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('enrolled_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->dateTime('d/m/Y')
                    ->placeholder('à vie')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(EnrollmentStatus::class),

                SelectFilter::make('source')
                    ->label('Origine')
                    ->options(EnrollmentSource::class),

                SelectFilter::make('course')
                    ->label('Formation')
                    ->relationship('course', 'title'),
            ])
            ->recordActions([
                // Revocation immediate d'un acces.
                Action::make('revoke')
                    ->label('Révoquer')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Enrollment $record): bool => $record->status === EnrollmentStatus::Active)
                    ->action(fn (Enrollment $record) => $record->update(['status' => EnrollmentStatus::Revoked])),

                // Restauration d'un acces revoque ou echu.
                Action::make('restore')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Enrollment $record): bool => $record->status !== EnrollmentStatus::Active)
                    ->action(fn (Enrollment $record) => $record->update(['status' => EnrollmentStatus::Active])),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc');
    }
}
