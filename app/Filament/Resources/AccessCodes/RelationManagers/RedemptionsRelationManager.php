<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessCodes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Bénéficiaires d'un code : en lecture seule. On répond à « qui a utilisé ce code, et
 * quand ». Aucune création ni suppression manuelle — le journal reflète les activations.
 */
class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $title = 'Bénéficiaires';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Apprenant')
                    ->searchable(),

                TextColumn::make('user.email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('user.phone')
                    ->label('Téléphone')
                    ->toggleable(),

                TextColumn::make('redeemed_at')
                    ->label('Activé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('redeemed_at', 'desc');
    }
}
