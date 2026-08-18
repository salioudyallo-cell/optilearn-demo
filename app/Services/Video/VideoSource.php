<?php

declare(strict_types=1);

namespace App\Services\Video;

/**
 * Ce que le serveur transmet au lecteur apres autorisation.
 *
 * « kind » indique au client comment lire la video :
 *  - iframe : lecteur distant embarque (Bunny Stream) ;
 *  - file   : element <video> natif alimente par une URL signee de notre serveur.
 *
 * Dans les deux cas, l'identifiant reel de la video (bunny_video_id ou chemin sur le
 * disque) n'est jamais transmis au client (§7.4).
 */
final readonly class VideoSource
{
    public function __construct(
        public string $kind,
        public string $url,
    ) {}

    public static function iframe(string $url): self
    {
        return new self('iframe', $url);
    }

    public static function file(string $url): self
    {
        return new self('file', $url);
    }

    /**
     * @return array{kind: string, url: string}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind, 'url' => $this->url];
    }
}
