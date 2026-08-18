<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Réglages du quiz')
                    ->columns(2)
                    ->schema([
                        TextInput::make('pass_score_pct')
                            ->label('Score de passage (%)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(70)
                            ->required(),

                        TextInput::make('max_attempts')
                            ->label('Tentatives autorisées')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(3)
                            ->required(),
                    ]),
            ]);
    }
}
