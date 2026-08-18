<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->user_id === $user->getKey()) {
            return true;
        }

        return $this->manages($user, $enrollment);
    }

    /**
     * L'inscription manuelle et la revocation sont des actes d'administration :
     * un formateur ne les exerce que sur ses propres cours.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $this->manages($user, $enrollment);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    private function manages(User $user, Enrollment $enrollment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor()
            && $enrollment->course?->instructor_id === $user->getKey();
    }
}
