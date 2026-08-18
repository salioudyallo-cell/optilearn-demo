<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    /**
     * Ressource sans page « index » (gérée depuis la leçon) : fil d'Ariane explicite
     * pour éviter la génération d'une URL vers un index inexistant.
     *
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $module = $this->record->lesson?->module;

        $trail = [];
        if ($module?->course !== null) {
            $trail[CourseResource::getUrl('edit', ['record' => $module->course_id])] = $module->course->title;
        }
        if ($module !== null) {
            $trail[ModuleResource::getUrl('edit', ['record' => $module->getKey()])] = $module->title;
        }
        $trail['#'] = 'Quiz';

        return $trail;
    }

    /**
     * Apres edition, on revient au module (gestion des lecons) dont depend le quiz.
     */
    protected function getRedirectUrl(): string
    {
        $moduleId = $this->record->lesson?->module_id;

        if ($moduleId !== null) {
            return ModuleResource::getUrl('edit', ['record' => $moduleId]);
        }

        return QuizResource::getUrl('edit', ['record' => $this->record]);
    }
}
