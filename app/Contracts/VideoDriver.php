<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Lesson;
use App\Services\Video\VideoSource;

/**
 * Pilote d'hebergement video. Deux implementations interchangeables (local, Bunny) :
 * changer VIDEO_DRIVER suffit a basculer, sans recreer le moindre contenu.
 */
interface VideoDriver
{
    /**
     * Le pilote dispose-t-il de tout ce qu'il lui faut pour fonctionner ?
     */
    public function isConfigured(): bool;

    /**
     * Cette lecon reference-t-elle une video exploitable par ce pilote ?
     */
    public function hasVideo(Lesson $lesson): bool;

    /**
     * Source de lecture a transmettre au client. N'est appele qu'APRES autorisation.
     */
    public function sourceFor(Lesson $lesson): VideoSource;
}
