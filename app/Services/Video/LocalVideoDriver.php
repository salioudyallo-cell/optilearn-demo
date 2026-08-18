<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Contracts\VideoDriver;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Lecture d'une video auto-hebergee. Le fichier vit hors du dossier public : le client
 * ne recoit qu'une URL signee et temporaire vers notre route de lecture, qui verifie a
 * nouveau l'autorisation avant de servir le moindre octet.
 *
 * Le chemin du fichier sur le disque n'est jamais transmis au client.
 */
final readonly class LocalVideoDriver implements VideoDriver
{
    public function isConfigured(): bool
    {
        // Aucun service tiers a configurer : le disque local suffit.
        return true;
    }

    public function hasVideo(Lesson $lesson): bool
    {
        if (blank($lesson->video_path)) {
            return false;
        }

        return Storage::disk($this->disk())->exists($lesson->video_path);
    }

    public function sourceFor(Lesson $lesson): VideoSource
    {
        $ttl = (int) config('lms.video.signed_url_ttl');

        return VideoSource::file(URL::temporarySignedRoute(
            'learn.video-stream',
            now()->addSeconds($ttl),
            ['lesson' => $lesson->getKey()],
        ));
    }

    private function disk(): string
    {
        return (string) config('lms.video.disk');
    }
}
