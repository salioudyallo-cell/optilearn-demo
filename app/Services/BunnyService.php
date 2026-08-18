<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client de l'API Bunny Stream pour la creation et l'upload de videos depuis le back-office.
 *
 * Flux d'upload : createVideo() cree l'entree et renvoie son GUID (= bunny_video_id), puis
 * uploadVideo() envoie le fichier. Le GUID est stocke sur la lecon ; le fichier ne transite
 * jamais par storage/ ni public/ (contrainte mutualise).
 *
 * Les methodes ne sont appelees que si isConfigured() : sans credentials, le back-office
 * fonctionne en saisie manuelle du bunny_video_id.
 */
final class BunnyService
{
    private const BASE_URL = 'https://video.bunnycdn.com';

    public function isConfigured(): bool
    {
        return filled(config('lms.bunny.api_key')) && filled(config('lms.bunny.library_id'));
    }

    /**
     * Cree une entree video et renvoie son identifiant (GUID).
     */
    public function createVideo(string $title): string
    {
        $libraryId = (string) config('lms.bunny.library_id');

        $response = $this->client()
            ->post(self::BASE_URL."/library/{$libraryId}/videos", ['title' => $title]);

        $response->throw();

        $guid = $response->json('guid');

        if (! is_string($guid) || $guid === '') {
            throw new RuntimeException('Bunny n’a pas renvoyé d’identifiant de vidéo.');
        }

        return $guid;
    }

    /**
     * Televerse le contenu d'un fichier vers une video existante.
     */
    public function uploadVideo(string $videoId, string $absolutePath): void
    {
        $libraryId = (string) config('lms.bunny.library_id');
        $stream = Utils::streamFor(Utils::tryFopen($absolutePath, 'rb'));

        $this->client()
            ->withBody($stream, 'application/octet-stream')
            ->put(self::BASE_URL."/library/{$libraryId}/videos/{$videoId}")
            ->throw();
    }

    private function client(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Bunny Stream n’est pas configuré : renseignez BUNNY_API_KEY et '
                .'BUNNY_LIBRARY_ID dans le fichier .env.'
            );
        }

        return Http::withHeaders([
            'AccessKey' => (string) config('lms.bunny.api_key'),
            'Accept' => 'application/json',
        ])->timeout(120);
    }
}
