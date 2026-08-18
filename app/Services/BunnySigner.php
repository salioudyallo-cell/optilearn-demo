<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lesson;
use RuntimeException;

/**
 * Genere les URLs signees du lecteur Bunny Stream. Le bunny_video_id n'est jamais rendu
 * dans le HTML : le controleur du lecteur appelle ce service APRES autorisation, et ne
 * transmet au client qu'une URL a duree de vie limitee (4 h par defaut).
 *
 * Reference du schema de signature : « Token Authentication » de Bunny Stream
 * (SHA-256 de security_key + path + expiration).
 */
final class BunnySigner
{
    public function isConfigured(): bool
    {
        return filled(config('lms.bunny.token_security_key'))
            && filled(config('lms.bunny.cdn_hostname'))
            && filled(config('lms.bunny.library_id'));
    }

    /**
     * URL de lecture signee pour l'iframe du lecteur Bunny.
     */
    public function playerUrl(Lesson $lesson, ?int $ttlSeconds = null): string
    {
        $videoId = $lesson->bunny_video_id;

        if (blank($videoId)) {
            throw new RuntimeException('La leçon ne référence aucune vidéo Bunny.');
        }

        $this->assertConfigured();

        $libraryId = (string) config('lms.bunny.library_id');
        $ttl = $ttlSeconds ?? (int) config('lms.bunny.signed_url_ttl');
        $expires = now()->addSeconds($ttl)->timestamp;

        $path = "/embed/{$libraryId}/{$videoId}";
        $token = $this->signature($path, $expires);

        $host = rtrim((string) config('lms.bunny.cdn_hostname'), '/');

        return "https://{$host}{$path}?token={$token}&expires={$expires}";
    }

    /**
     * Signature SHA-256 d'un chemin pour une expiration donnee.
     */
    public function signature(string $path, int $expires): string
    {
        $securityKey = (string) config('lms.bunny.token_security_key');

        return hash('sha256', $securityKey.$path.$expires);
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Bunny Stream n’est pas configuré : renseignez BUNNY_LIBRARY_ID, '
                .'BUNNY_CDN_HOSTNAME et BUNNY_TOKEN_SECURITY_KEY dans le fichier .env.'
            );
        }
    }
}
