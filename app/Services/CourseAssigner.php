<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\User;

/**
 * Affectation de formations aux apprenants d'une organisation (mode Entreprise).
 *
 * Principe : affecter une formation à un groupe = créer les inscriptions de ses membres.
 * Le cœur d'accès reste ainsi INCHANGÉ (hasAccessToCourse s'appuie sur les inscriptions).
 * Toutes les opérations sont idempotentes : les relancer ne crée aucun doublon.
 */
class CourseAssigner
{
    /**
     * Affecte une formation à un groupe et inscrit ses membres. Renvoie le nombre
     * d'inscriptions créées.
     */
    public function assignCourseToGroup(Course $course, Group $group): int
    {
        $group->courses()->syncWithoutDetaching([
            $course->getKey() => ['assigned_at' => now()],
        ]);

        $created = 0;
        foreach ($group->members()->get() as $member) {
            $created += $this->ensureEnrollment($member, $course) ? 1 : 0;
        }

        return $created;
    }

    /**
     * Réconcilie tout un groupe : chaque membre est inscrit à chaque formation affectée.
     * À appeler après un changement de membres ou de formations du groupe.
     */
    public function reconcileGroup(Group $group): int
    {
        $courses = $group->courses()->get();
        $members = $group->members()->get();

        $created = 0;
        foreach ($members as $member) {
            foreach ($courses as $course) {
                $created += $this->ensureEnrollment($member, $course) ? 1 : 0;
            }
        }

        return $created;
    }

    /**
     * Réconcilie un apprenant : inscrit à toutes les formations de tous ses groupes.
     * À appeler quand un apprenant rejoint un ou plusieurs groupes.
     */
    public function reconcileUser(User $user): int
    {
        $created = 0;
        foreach ($user->groups()->with('courses')->get() as $group) {
            foreach ($group->courses as $course) {
                $created += $this->ensureEnrollment($user, $course) ? 1 : 0;
            }
        }

        return $created;
    }

    /**
     * Crée l'inscription si elle n'existe pas déjà. Renvoie true si une ligne a été créée.
     */
    private function ensureEnrollment(User $user, Course $course): bool
    {
        $enrollment = Enrollment::firstOrCreate(
            [
                'user_id' => $user->getKey(),
                'course_id' => $course->getKey(),
            ],
            [
                'status' => EnrollmentStatus::Active,
                'source' => EnrollmentSource::Manual,
                'enrolled_at' => now(),
            ],
        );

        return $enrollment->wasRecentlyCreated;
    }
}
