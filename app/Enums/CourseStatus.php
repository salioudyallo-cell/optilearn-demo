<?php

declare(strict_types=1);

namespace App\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('courses.status.draft'),
            self::Published => __('courses.status.published'),
            self::Archived => __('courses.status.archived'),
        };
    }
}
