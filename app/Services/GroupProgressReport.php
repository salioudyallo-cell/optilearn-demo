<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Tableau de bord RH : progression des membres d'un groupe sur les formations qui lui
 * sont affectées. Sert à l'affichage back-office et à l'export CSV.
 */
class GroupProgressReport
{
    public function __construct(
        private readonly LearningNavigator $navigator,
        private readonly CourseCompletion $completion,
    ) {}

    /**
     * Une ligne par membre.
     *
     * @return Collection<int, array{user: User, courses_total: int, courses_done: int, lessons_total: int, lessons_done: int, percent: int, certificates: int}>
     */
    public function forGroup(Group $group): Collection
    {
        /** @var Collection<int, Course> $courses */
        $courses = $group->courses()->get();

        return $group->members()->get()->map(
            fn (User $member): array => $this->forMember($member, $courses)
        )->values();
    }

    /**
     * Progression synthétique d'un membre sur un ensemble de formations.
     *
     * @param  Collection<int, Course>  $courses
     * @return array{user: User, courses_total: int, courses_done: int, lessons_total: int, lessons_done: int, percent: int, certificates: int}
     */
    public function forMember(User $member, Collection $courses): array
    {
        $lessonsTotal = 0;
        $lessonsDone = 0;
        $coursesDone = 0;

        foreach ($courses as $course) {
            $total = $this->navigator->orderedLessons($course)->count();
            $done = $this->navigator->completedLessonIds($member, $course)->count();

            $lessonsTotal += $total;
            $lessonsDone += min($done, $total);

            if ($total > 0 && $this->completion->isComplete($member, $course)) {
                $coursesDone++;
            }
        }

        $percent = $lessonsTotal > 0 ? (int) round($lessonsDone / $lessonsTotal * 100) : 0;

        return [
            'user' => $member,
            'courses_total' => $courses->count(),
            'courses_done' => $coursesDone,
            'lessons_total' => $lessonsTotal,
            'lessons_done' => $lessonsDone,
            'percent' => $percent,
            'certificates' => $member->certificates()->count(),
        ];
    }
}
