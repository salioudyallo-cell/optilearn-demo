<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentEnrollments extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTableHeading(): string
    {
        return 'Inscriptions récentes';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Enrollment::query()
                    ->with(['user', 'course'])
                    ->latest('enrolled_at')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Apprenant')
                    ->description(fn (Enrollment $record): ?string => $record->course?->title),

                TextColumn::make('source')
                    ->label('Origine')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (EnrollmentStatus $state): string => match ($state) {
                        EnrollmentStatus::Active => 'success',
                        EnrollmentStatus::Expired => 'warning',
                        EnrollmentStatus::Revoked => 'danger',
                    }),

                TextColumn::make('enrolled_at')
                    ->label('Le')
                    ->since(),
            ])
            ->paginated(false);
    }
}
