<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Contracts\VideoDriver;
use App\Models\Lesson;
use App\Services\BunnySigner;

/**
 * Lecture via Bunny Stream : le client recoit une URL d'iframe signee, a duree de vie
 * limitee. Le bunny_video_id ne quitte jamais le serveur.
 */
final readonly class BunnyVideoDriver implements VideoDriver
{
    public function __construct(private BunnySigner $signer) {}

    public function isConfigured(): bool
    {
        return $this->signer->isConfigured();
    }

    public function hasVideo(Lesson $lesson): bool
    {
        return filled($lesson->bunny_video_id);
    }

    public function sourceFor(Lesson $lesson): VideoSource
    {
        return VideoSource::iframe($this->signer->playerUrl($lesson));
    }
}
