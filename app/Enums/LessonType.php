<?php

declare(strict_types=1);

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Text = 'text';
    case Pdf = 'pdf';
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::Video => __('lessons.type.video'),
            self::Text => __('lessons.type.text'),
            self::Pdf => __('lessons.type.pdf'),
            self::Quiz => __('lessons.type.quiz'),
        };
    }
}
