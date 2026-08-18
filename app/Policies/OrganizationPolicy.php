<?php

declare(strict_types=1);

namespace App\Policies;

use App\Facades\Platform;
use App\Models\Organization;
use App\Models\User;

/**
 * Gestion des organisations : réservée à l'administrateur ET uniquement quand la capacité
 * « organizations » est active (mode Entreprise). En mode Commercial, la ressource
 * disparaît entièrement du back-office.
 */
class OrganizationPolicy
{
    private function enabled(User $user): bool
    {
        return $user->isAdmin() && Platform::allows('organizations');
    }

    public function viewAny(User $user): bool
    {
        return $this->enabled($user);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->enabled($user);
    }

    public function create(User $user): bool
    {
        return $this->enabled($user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->enabled($user);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->enabled($user);
    }
}
