<?php

declare(strict_types=1);

namespace App\Services;

use App\Facades\Track;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Tracking\EventName;

/**
 * Enregistrement de la progression d'un apprenant. Regroupe les ecritures sur
 * lesson_progress ; appele par le composant Livewire, jamais l'inverse (regle §1).
 */
final class ProgressTracker
{
    /**
     * Met a jour la derniere position lue d'une video (en secondes).
     */
    public function savePosition(User $user, Lesson $lesson, int $positionSeconds): LessonProgress
    {
        return LessonProgress::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'lesson_id' => $lesson->getKey()],
            ['last_position_seconds' => max(0, $positionSeconds)],
        );
    }

    /**
     * Marque une lecon comme terminee (idempotent).
     */
    public function markCompleted(User $user, Lesson $lesson): LessonProgress
    {
        $progress = LessonProgress::query()->firstOrNew(
            ['user_id' => $user->getKey(), 'lesson_id' => $lesson->getKey()],
        );

        $newlyCompleted = $progress->completed_at === null;
        if ($newlyCompleted) {
            $progress->completed_at = now();
        }

        $progress->save();

        if ($newlyCompleted) {
            Track::event(EventName::LessonCompleted, ['lesson_id' => $lesson->getKey()], $user);
        }

        return $progress;
    }

    public function positionFor(User $user, Lesson $lesson): int
    {
        $progress = LessonProgress::query()
            ->where('user_id', $user->getKey())
            ->where('lesson_id', $lesson->getKey())
            ->first();

        return $progress === null ? 0 : $progress->last_position_seconds;
    }

    public function isCompleted(User $user, Lesson $lesson): bool
    {
        return LessonProgress::query()
            ->where('user_id', $user->getKey())
            ->where('lesson_id', $lesson->getKey())
            ->whereNotNull('completed_at')
            ->exists();
    }
}
