<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Navigation et progression au sein d'une formation. Aucune logique metier dans les
 * controleurs ni les composants Livewire (regle §1) : tout passe par ce service.
 */
final class LearningNavigator
{
    /**
     * Toutes les lecons d'une formation, dans l'ordre de lecture
     * (module.position, puis lesson.position).
     *
     * @return Collection<int, Lesson>
     */
    public function orderedLessons(Course $course): Collection
    {
        return $course->modules
            ->sortBy('position')
            ->flatMap(fn ($module) => $module->lessons->sortBy('position'))
            ->values();
    }

    /**
     * Identifiants des lecons terminees par l'utilisateur pour cette formation.
     *
     * @return Collection<int, int>
     */
    public function completedLessonIds(User $user, Course $course): Collection
    {
        $lessonIds = $this->orderedLessons($course)->pluck('id');

        return $user->lessonProgress()
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');
    }

    /**
     * @return array{completed: int, total: int, percent: int}
     */
    public function progressFor(User $user, Course $course): array
    {
        $total = $this->orderedLessons($course)->count();
        $completed = $this->completedLessonIds($user, $course)->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];
    }

    /**
     * Lecon a reprendre : la premiere non terminee, sinon la premiere de la formation.
     */
    public function resumeLesson(User $user, Course $course): ?Lesson
    {
        $lessons = $this->orderedLessons($course);
        $completed = $this->completedLessonIds($user, $course);

        return $lessons->first(fn (Lesson $lesson) => ! $completed->contains($lesson->id))
            ?? $lessons->first();
    }

    public function previousLesson(Lesson $lesson): ?Lesson
    {
        $course = $lesson->module?->course;

        if ($course === null) {
            return null;
        }

        $lessons = $this->orderedLessons($course);
        $index = $lessons->search(fn (Lesson $item) => $item->id === $lesson->id);

        return $index > 0 ? $lessons->get($index - 1) : null;
    }

    public function nextLesson(Lesson $lesson): ?Lesson
    {
        $course = $lesson->module?->course;

        if ($course === null) {
            return null;
        }

        $lessons = $this->orderedLessons($course);
        $index = $lessons->search(fn (Lesson $item) => $item->id === $lesson->id);

        return $index !== false ? $lessons->get($index + 1) : null;
    }
}
