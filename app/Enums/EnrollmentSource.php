<?php

declare(strict_types=1);

namespace App\Enums;

enum EnrollmentSource: string
{
    case AccessCode = 'access_code';
    case Manual = 'manual';
    /** Reserve a la phase 7 (paiement en ligne), jamais produit par le code actuel. */
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::AccessCode => __('enrollments.source.access_code'),
            self::Manual => __('enrollments.source.manual'),
            self::Purchase => __('enrollments.source.purchase'),
        };
    }
}
