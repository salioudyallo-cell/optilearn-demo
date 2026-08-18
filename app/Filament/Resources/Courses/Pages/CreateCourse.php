<?php

declare(strict_types=1);

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    /**
     * Un formateur est toujours proprietaire des formations qu'il cree : le champ formateur
     * lui est masque, on force donc son identifiant cote serveur.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user !== null && ! $user->isAdmin()) {
            $data['instructor_id'] = $user->getKey();
        }

        return $data;
    }
}
