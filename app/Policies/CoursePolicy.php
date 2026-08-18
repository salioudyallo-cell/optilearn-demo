<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * L'administrateur a tous les droits. Retourne null pour un invite ou un non-admin
     * afin de laisser les autres methodes decider.
     */
    public function before(?User $user, string $ability): ?bool
    {
        return $user?->isAdmin() ? true : null;
    }

    /**
     * Le catalogue est public : la restriction porte sur les cours non publies,
     * traitee par view().
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function reorder(User $user): bool
    {
        return $user->isInstructor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isInstructor();
    }

    /**
     * La fiche d'un cours publie est publique (vitrine). Un brouillon ou une archive
     * n'est visible que de son formateur et des administrateurs.
     */
    public function view(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        return $user !== null && $this->owns($user, $course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->owns($user, $course);
    }

    /**
     * Un formateur n'a de droits que sur ses propres cours. L'administrateur n'a pas
     * cette restriction.
     */
    private function owns(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor() && $course->instructor_id === $user->getKey();
    }
}
