<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Contracts\VideoDriver;
use App\Models\Lesson;
use InvalidArgumentException;

/**
 * Selectionne le pilote video actif (config lms.video.driver) et sert de point d'entree
 * unique aux controleurs. Basculer de « local » vers « bunny » ne change que le .env.
 */
final class VideoManager
{
    public function __construct(
        private readonly LocalVideoDriver $local,
        private readonly BunnyVideoDriver $bunny,
    ) {}

    public function driver(?string $name = null): VideoDriver
    {
        return match ($name ?? (string) config('lms.video.driver')) {
            'local' => $this->local,
            'bunny' => $this->bunny,
            default => throw new InvalidArgumentException(
                'Pilote video inconnu : renseignez VIDEO_DRIVER avec « local » ou « bunny ».'
            ),
        };
    }

    public function isConfigured(): bool
    {
        return $this->driver()->isConfigured();
    }

    public function hasVideo(Lesson $lesson): bool
    {
        return $this->driver()->hasVideo($lesson);
    }

    public function sourceFor(Lesson $lesson): VideoSource
    {
        return $this->driver()->sourceFor($lesson);
    }
}
