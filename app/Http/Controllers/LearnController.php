<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseCompletion;
use App\Services\LearningNavigator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LearnController extends Controller
{
    /**
     * Espace apprenant : les formations auxquelles l'utilisateur a acces.
     */
    public function dashboard(LearningNavigator $navigator, CourseCompletion $completion): View
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $enrollments = $user->enrollments()
            ->grantingAccess()
            ->with(['course.modules.lessons'])
            ->get();

        return view('learn.dashboard', [
            'enrollments' => $enrollments,
            'resume' => $this->resumeTarget($user, $enrollments, $navigator, $completion),
        ]);
    }

    /**
     * Formation à reprendre en priorité : la plus récemment travaillée qui n'est pas
     * encore terminée. Alimente le bandeau « Reprendre » du tableau de bord.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @return array{course: Course, lesson: Lesson, percent: int}|null
     */
    private function resumeTarget(User $user, Collection $enrollments, LearningNavigator $navigator, CourseCompletion $completion): ?array
    {
        $best = null;
        $bestTime = null;

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;

            if ($completion->isComplete($user, $course)) {
                continue;
            }

            $lesson = $navigator->resumeLesson($user, $course);
            if ($lesson === null) {
                continue;
            }

            $total = $navigator->orderedLessons($course)->count();
            $done = $navigator->completedLessonIds($user, $course)->count();
            $percent = $total > 0 ? (int) round($done / $total * 100) : 0;

            $lastActivity = LessonProgress::query()
                ->where('user_id', $user->getKey())
                ->whereHas('lesson.module', fn ($q) => $q->where('course_id', $course->getKey()))
                ->max('updated_at');

            $time = $lastActivity !== null ? Carbon::parse($lastActivity) : $enrollment->enrolled_at;

            if ($bestTime === null || $time->greaterThan($bestTime)) {
                $bestTime = $time;
                $best = ['course' => $course, 'lesson' => $lesson, 'percent' => $percent];
            }
        }

        return $best;
    }

    /**
     * Sommaire d'une formation. Redirige vers la premiere lecon a reprendre.
     */
    public function course(Course $course, LearningNavigator $navigator): View
    {
        $this->authorize('view', $course);

        $user = auth()->user();
        abort_unless($user !== null && $user->hasAccessToCourse($course), 403);

        $course->load(['modules.lessons']);

        return view('learn.course', [
            'course' => $course,
            'progress' => $navigator->progressFor($user, $course),
            'resumeLesson' => $navigator->resumeLesson($user, $course),
        ]);
    }

    /**
     * Lecteur d'une lecon. L'autorisation fine (preview publique ou inscription)
     * est portee par LessonPolicy — jamais un Lesson::find non protege (§7.4).
     */
    public function lesson(Lesson $lesson, LearningNavigator $navigator): View
    {
        $this->authorize('view', $lesson);

        $lesson->load(['module.course.modules.lessons', 'quiz']);
        $course = $lesson->module?->course;
        abort_if($course === null, 404);

        return view('learn.lesson', [
            'lesson' => $lesson,
            'course' => $course,
            'previous' => $navigator->previousLesson($lesson),
            'next' => $navigator->nextLesson($lesson),
        ]);
    }
}
