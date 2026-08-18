<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AccessCode;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CodesRunningLow extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /**
     * Seuil « bientôt épuisé » : au plus ce nombre d'utilisations restantes.
     */
    private const REMAINING_THRESHOLD = 3;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTableHeading(): string
    {
        return 'Codes bientôt épuisés';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->query())
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->copyable(),

                TextColumn::make('label')
                    ->label('Libellé')
                    ->description(fn (AccessCode $record): ?string => $record->course?->title)
                    ->wrap(),

                TextColumn::make('remaining')
                    ->label('Restant')
                    ->state(fn (AccessCode $record): string => $record->remainingUses().' / '.$record->max_uses)
                    ->badge()
                    ->color('warning'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Aucun code proche de l’épuisement');
    }

    /**
     * Codes actifs, non épuisés, dont le nombre d'usages restants est faible.
     * Filtre exprimé sans fonction propriétaire (arithmétique ANSI) pour rester
     * portable MySQL / MariaDB / PostgreSQL (règle §2).
     *
     * @return Builder<AccessCode>
     */
    private function query(): Builder
    {
        return AccessCode::query()
            ->with('course')
            ->where('is_active', true)
            ->whereColumn('used_count', '<', 'max_uses')
            ->whereRaw('max_uses - used_count <= ?', [self::REMAINING_THRESHOLD])
            ->orderByRaw('max_uses - used_count asc')
            ->limit(8);
    }
}
