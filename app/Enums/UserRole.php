<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Learner = 'learner';
    case Instructor = 'instructor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Learner => __('roles.learner'),
            self::Instructor => __('roles.instructor'),
            self::Admin => __('roles.admin'),
        };
    }

    /**
     * Seuls ces roles accedent au panel Filament.
     */
    public function canAccessAdminPanel(): bool
    {
        return in_array($this, [self::Instructor, self::Admin], strict: true);
    }
}
