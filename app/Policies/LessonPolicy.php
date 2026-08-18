<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

/**
 * Point le plus critique de la securite du produit : il n'existe aucun garde-fou au
 * niveau de la base. Toute requete retournant du contenu de lecon doit passer par
 * view(). Un `Lesson::find($id)` non protege fait fuiter le contenu payant.
 */
class LessonPolicy
{
    /**
     * L'administrateur a tous les droits. Null pour les invites et non-admins afin de
     * laisser les methodes specifiques (notamment la preview publique) decider.
     */
    public function before(?User $user, string $ability): ?bool
    {
        return $user?->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function create(User $user): bool
    {
        return $user->isInstructor();
    }

    public function reorder(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(?User $user, Lesson $lesson): bool
    {
        $course = $lesson->module?->course;

        if ($course === null) {
            return false;
        }

        // La lecon d'essai est le seul contenu accessible sans inscription, et
        // uniquement si le cours lui-meme est publie.
        if ($lesson->is_preview && $course->isPublished()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor() && $course->instructor_id === $user->getKey()) {
            return true;
        }

        return $user->hasAccessToCourse($course);
    }

    /**
     * Le telechargement d'un PDF de cours suit exactement la meme regle que la
     * consultation : pas de porte derobee par l'URL de l'asset.
     */
    public function download(?User $user, Lesson $lesson): bool
    {
        return $this->view($user, $lesson);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        $course = $lesson->module?->course;

        if ($course === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor() && $course->instructor_id === $user->getKey();
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
