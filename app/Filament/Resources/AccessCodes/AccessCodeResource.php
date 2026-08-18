<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessCodes;

use App\Filament\Resources\AccessCodes\Pages\CreateAccessCode;
use App\Filament\Resources\AccessCodes\Pages\EditAccessCode;
use App\Filament\Resources\AccessCodes\Pages\ListAccessCodes;
use App\Filament\Resources\AccessCodes\RelationManagers\RedemptionsRelationManager;
use App\Filament\Resources\AccessCodes\Schemas\AccessCodeForm;
use App\Filament\Resources\AccessCodes\Tables\AccessCodesTable;
use App\Models\AccessCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccessCodeResource extends Resource
{
    protected static ?string $model = AccessCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Codes d’accès';

    protected static ?string $modelLabel = 'code d’accès';

    protected static ?string $pluralModelLabel = 'codes d’accès';

    protected static string|\UnitEnum|null $navigationGroup = 'Accès';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return AccessCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccessCodesTable::configure($table);
    }

    /**
     * Un formateur ne voit que les codes de ses propres formations.
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
            RedemptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccessCodes::route('/'),
            'create' => CreateAccessCode::route('/create'),
            'edit' => EditAccessCode::route('/{record}/edit'),
        ];
    }
}
