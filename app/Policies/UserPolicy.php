<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * La gestion des comptes est réservée à l'administrateur.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // Un administrateur ne peut pas supprimer son propre compte depuis cette liste.
        return $user->isAdmin() && $user->getKey() !== $model->getKey();
    }
}
