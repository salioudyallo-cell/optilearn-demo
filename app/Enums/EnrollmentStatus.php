<?php

declare(strict_types=1);

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('enrollments.status.active'),
            self::Expired => __('enrollments.status.expired'),
            self::Revoked => __('enrollments.status.revoked'),
        };
    }
}
