<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    /**
     * L'administrateur a tous les droits sur les modules.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(User $user, Module $module): bool
    {
        return $this->owns($user, $module);
    }

    public function create(User $user): bool
    {
        return $user->isInstructor();
    }

    public function update(User $user, Module $module): bool
    {
        return $this->owns($user, $module);
    }

    public function delete(User $user, Module $module): bool
    {
        return $this->owns($user, $module);
    }

    public function reorder(User $user): bool
    {
        return $user->isInstructor();
    }

    private function owns(User $user, Module $module): bool
    {
        return $user->isInstructor() && $module->course?->instructor_id === $user->getKey();
    }
}
