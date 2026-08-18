<?php

declare(strict_types=1);

namespace App\Policies;

use App\Facades\Platform;
use App\Models\Group;
use App\Models\User;

/**
 * Gestion des groupes : administrateur uniquement, et seulement quand la capacité
 * « groups » est active (mode Entreprise).
 */
class GroupPolicy
{
    private function enabled(User $user): bool
    {
        return $user->isAdmin() && Platform::allows('groups');
    }

    public function viewAny(User $user): bool
    {
        return $this->enabled($user);
    }

    public function view(User $user, Group $group): bool
    {
        return $this->enabled($user);
    }

    public function create(User $user): bool
    {
        return $this->enabled($user);
    }

    public function update(User $user, Group $group): bool
    {
        return $this->enabled($user);
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->enabled($user);
    }
}
