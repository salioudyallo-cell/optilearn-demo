<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function create(User $user): bool
    {
        return $user->isInstructor();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->owns($user, $quiz);
    }

    private function owns(User $user, Quiz $quiz): bool
    {
        return $user->isInstructor()
            && $quiz->lesson?->module?->course?->instructor_id === $user->getKey();
    }
}
