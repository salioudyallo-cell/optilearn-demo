<?php

declare(strict_types=1);

namespace App\Filament\Resources\AccessCodes\Pages;

use App\Filament\Resources\AccessCodes\AccessCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccessCode extends CreateRecord
{
    protected static string $resource = AccessCodeResource::class;

    /**
     * Le createur du code est toujours l'utilisateur connecte.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['used_count'] = 0;

        return $data;
    }
}
