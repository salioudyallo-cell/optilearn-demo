<?php

declare(strict_types=1);

namespace App\Enums;

enum CourseLevel: string
{
    case Debutant = 'debutant';
    case Intermediaire = 'intermediaire';
    case Avance = 'avance';

    public function label(): string
    {
        return match ($this) {
            self::Debutant => __('courses.level.debutant'),
            self::Intermediaire => __('courses.level.intermediaire'),
            self::Avance => __('courses.level.avance'),
        };
    }
}
