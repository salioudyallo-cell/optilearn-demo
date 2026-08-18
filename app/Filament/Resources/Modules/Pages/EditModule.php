<?php

declare(strict_types=1);

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Modules\ModuleResource;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    /**
     * Ressource sans page « index » (gérée depuis la formation) : on ne peut pas
     * générer le fil d'Ariane par défaut qui pointerait vers cet index inexistant.
     *
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $course = $this->record->course;

        return [
            CourseResource::getUrl('edit', ['record' => $this->record->course_id]) => $course?->title ?? 'Formation',
            '#' => 'Leçons du module',
        ];
    }

    /**
     * Apres edition, on revient a la formation dont depend le module.
     */
    protected function getRedirectUrl(): string
    {
        return CourseResource::getUrl('edit', ['record' => $this->record->course_id]);
    }
}
