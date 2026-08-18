<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QuizQuestion;
use App\Models\User;

class QuizQuestionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isInstructor();
    }

    public function view(User $user, QuizQuestion $question): bool
    {
        return $this->owns($user, $question);
    }

    public function create(User $user): bool
    {
        return $user->isInstructor();
    }

    public function update(User $user, QuizQuestion $question): bool
    {
        return $this->owns($user, $question);
    }

    public function delete(User $user, QuizQuestion $question): bool
    {
        return $this->owns($user, $question);
    }

    public function reorder(User $user): bool
    {
        return $user->isInstructor();
    }

    private function owns(User $user, QuizQuestion $question): bool
    {
        return $user->isInstructor()
            && $question->quiz?->lesson?->module?->course?->instructor_id === $user->getKey();
    }
}
