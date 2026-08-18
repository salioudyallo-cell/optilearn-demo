<?php

declare(strict_types=1);

namespace App\Enums;

enum RedemptionOutcome: string
{
    case Redeemed = 'redeemed';
    /** Cas nominal, pas une erreur : l'apprenant a deja acces a cette formation. */
    case AlreadyEnrolled = 'already_enrolled';
    case NotFound = 'not_found';
    case Inactive = 'inactive';
    case Expired = 'expired';
    case Exhausted = 'exhausted';
    case CourseUnavailable = 'course_unavailable';
    case Throttled = 'throttled';

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Redeemed, self::AlreadyEnrolled], strict: true);
    }
}
