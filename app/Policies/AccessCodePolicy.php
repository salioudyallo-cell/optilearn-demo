<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccessCode;
use App\Models\User;

class AccessCodePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(User $user, AccessCode $accessCode): bool
    {
        return $this->owns($user, $accessCode);
    }

    public function create(User $user): bool
    {
        return $user->isInstructor();
    }

    public function update(User $user, AccessCode $accessCode): bool
    {
        return $this->owns($user, $accessCode);
    }

    public function delete(User $user, AccessCode $accessCode): bool
    {
        return $this->owns($user, $accessCode);
    }

    private function owns(User $user, AccessCode $accessCode): bool
    {
        return $user->isInstructor() && $accessCode->course?->instructor_id === $user->getKey();
    }
}
