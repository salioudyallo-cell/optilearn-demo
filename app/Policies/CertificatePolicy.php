<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Le PDF n'est accessible qu'a son titulaire, au formateur du cours et aux
     * administrateurs. La verification publique passe par une autre route, qui
     * n'expose que le serial, le nom et la date — jamais le fichier.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        if ($certificate->user_id === $user->getKey()) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor()
            && $certificate->course?->instructor_id === $user->getKey();
    }

    public function download(User $user, Certificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin();
    }
}
