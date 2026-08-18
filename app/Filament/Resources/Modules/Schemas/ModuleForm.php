<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // La formation d'appartenance est fixee a la creation depuis le relation manager.
                TextInput::make('course.title')
                    ->label('Formation')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('title')
                    ->label('Titre du module')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
