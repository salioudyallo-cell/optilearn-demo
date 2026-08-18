<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enrollments\Pages;

use App\Enums\EnrollmentSource;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    /**
     * Une inscription creee depuis le panel est toujours d'origine « manuelle ».
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = EnrollmentSource::Manual->value;
        $data['enrolled_at'] = now();

        return $data;
    }
}
